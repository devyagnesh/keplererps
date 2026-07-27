<?php

namespace App\Services;

use App\Enums\BalanceSide;
use App\Enums\DocumentStatus;
use App\Enums\LedgerAccountType;
use App\Models\JournalVoucherLine;
use App\Models\LedgerAccount;
use App\Models\Party;
use Illuminate\Support\Collection;

/**
 * Read-only finance reports: party ledgers, AR/AP ageing and trial balance (M13).
 */
class FinanceReportService
{
    /**
     * Ageing buckets in days; the last bucket is open-ended.
     *
     * @var list<int>
     */
    public const AGEING_BUCKETS = [30, 60, 90];

    public function __construct(
        protected LedgerAccountService $accounts,
        protected SystemSettingService $settings
    ) {}

    /**
     * Statement of a ledger account with a running balance.
     *
     * @return array{account: LedgerAccount, opening: float, closing: float, rows: list<array<string, mixed>>}
     */
    public function accountStatement(int $ledgerAccountId, ?string $fromDate = null, ?string $toDate = null, ?int $partyId = null): array
    {
        $account = $this->accounts->find($ledgerAccountId);

        $opening = $account->signedOpeningBalance() + $this->netMovement($account->id, null, $fromDate, $partyId, exclusiveTo: true);
        $running = $opening;
        $rows = [];

        $lines = $this->postedLines($account->id, $fromDate, $toDate, $partyId)
            ->with(['journalVoucher:id,document_no,document_date,voucher_type,reference_no', 'party:id,party_code,party_name'])
            ->get();

        foreach ($lines as $line) {
            $running = round($running + (float) $line->debit - (float) $line->credit, 2);
            $rows[] = [
                'document_no' => $line->journalVoucher?->document_no,
                'document_date' => $line->journalVoucher?->document_date?->toDateString(),
                'voucher_type' => $line->journalVoucher?->voucher_type->label(),
                'reference_no' => $line->journalVoucher?->reference_no,
                'party' => $line->party?->party_name,
                'narration' => $line->narration,
                'debit' => round((float) $line->debit, 2),
                'credit' => round((float) $line->credit, 2),
                'balance' => $running,
                'balance_side' => $running >= 0 ? BalanceSide::Debit->value : BalanceSide::Credit->value,
            ];
        }

        return [
            'account' => $account,
            'opening' => round($opening, 2),
            'closing' => round($running, 2),
            'rows' => $rows,
        ];
    }

    /**
     * Receivable or payable ageing by party.
     *
     * @param  'receivable'|'payable'  $type
     * @return list<array<string, mixed>>
     */
    public function ageing(string $type, ?string $asOnDate = null): array
    {
        $account = $this->controlAccount($type);
        if ($account === null) {
            return [];
        }

        $asOn = $asOnDate ?? now()->toDateString();
        $isReceivable = $type === 'receivable';

        $lines = $this->postedLines($account->id, null, $asOn)
            ->whereNotNull('journal_voucher_lines.party_id')
            ->with('journalVoucher:id,document_no,document_date')
            ->get()
            ->groupBy('party_id');

        $parties = Party::query()
            ->whereIn('id', $lines->keys()->all())
            ->get(['id', 'party_code', 'party_name'])
            ->keyBy('id');

        $rows = [];

        foreach ($lines as $partyId => $partyLines) {
            $open = $this->applyOnAccountAmounts($partyLines, $isReceivable);
            $outstanding = round(array_sum(array_column($open, 'amount')), 2);

            if (abs($outstanding) < 0.01) {
                continue;
            }

            $buckets = $this->bucketise($open, $asOn);
            $party = $parties->get($partyId);

            $rows[] = array_merge([
                'party_id' => (int) $partyId,
                'party_code' => $party?->party_code,
                'party_name' => $party?->party_name,
                'outstanding' => $outstanding,
            ], $buckets);
        }

        return Collection::make($rows)
            ->sortByDesc('outstanding')
            ->values()
            ->all();
    }

    /**
     * Trial balance across all accounts with movement or an opening balance.
     *
     * @return array{rows: list<array<string, mixed>>, total_debit: float, total_credit: float}
     */
    public function trialBalance(?string $fromDate = null, ?string $toDate = null): array
    {
        $accounts = LedgerAccount::query()->orderBy('code')->get();
        $rows = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($accounts as $account) {
            $movement = $this->movementTotals($account->id, $fromDate, $toDate);
            $closing = round($account->signedOpeningBalance() + $movement['debit'] - $movement['credit'], 2);

            if (abs($closing) < 0.01 && $movement['debit'] < 0.01 && $movement['credit'] < 0.01) {
                continue;
            }

            $debit = $closing > 0 ? $closing : 0.0;
            $credit = $closing < 0 ? abs($closing) : 0.0;
            $totalDebit += $debit;
            $totalCredit += $credit;

            $rows[] = [
                'code' => $account->code,
                'name' => $account->name,
                'account_type' => $account->account_type->label(),
                'debit_movement' => $movement['debit'],
                'credit_movement' => $movement['credit'],
                'closing_debit' => round($debit, 2),
                'closing_credit' => round($credit, 2),
            ];
        }

        return [
            'rows' => $rows,
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
        ];
    }

    /**
     * Profit &amp; loss for a period from income and expense ledger closings.
     *
     * @return array{from_date: ?string, to_date: ?string, income: list<array<string, mixed>>, expense: list<array<string, mixed>>, total_income: float, total_expense: float, net_profit: float}
     */
    public function profitAndLoss(?string $fromDate = null, ?string $toDate = null): array
    {
        $income = [];
        $expense = [];
        $totalIncome = 0.0;
        $totalExpense = 0.0;

        $accounts = LedgerAccount::query()
            ->whereIn('account_type', [LedgerAccountType::Income->value, LedgerAccountType::Expense->value])
            ->orderBy('code')
            ->get();

        foreach ($accounts as $account) {
            $movement = $this->movementTotals($account->id, $fromDate, $toDate);
            // Income normal credit; expense normal debit.
            $amount = $account->account_type === LedgerAccountType::Income
                ? round($movement['credit'] - $movement['debit'], 2)
                : round($movement['debit'] - $movement['credit'], 2);

            if (abs($amount) < 0.01) {
                continue;
            }

            $row = [
                'code' => $account->code,
                'name' => $account->name,
                'amount' => $amount,
            ];

            if ($account->account_type === LedgerAccountType::Income) {
                $income[] = $row;
                $totalIncome += $amount;
            } else {
                $expense[] = $row;
                $totalExpense += $amount;
            }
        }

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'income' => $income,
            'expense' => $expense,
            'total_income' => round($totalIncome, 2),
            'total_expense' => round($totalExpense, 2),
            'net_profit' => round($totalIncome - $totalExpense, 2),
        ];
    }

    /**
     * Balance sheet as-on a date (assets = liabilities + equity + current period P&amp;L).
     *
     * @return array{as_on_date: ?string, assets: list<array<string, mixed>>, liabilities: list<array<string, mixed>>, equity: list<array<string, mixed>>, total_assets: float, total_liabilities: float, total_equity: float, retained_earnings: float}
     */
    public function balanceSheet(?string $asOnDate = null): array
    {
        $asOnDate ??= now()->toDateString();
        $assets = [];
        $liabilities = [];
        $equity = [];
        $totalAssets = 0.0;
        $totalLiabilities = 0.0;
        $totalEquity = 0.0;

        $accounts = LedgerAccount::query()
            ->whereIn('account_type', [
                LedgerAccountType::Asset->value,
                LedgerAccountType::Liability->value,
                LedgerAccountType::Equity->value,
            ])
            ->orderBy('code')
            ->get();

        foreach ($accounts as $account) {
            $movement = $this->movementTotals($account->id, null, $asOnDate);
            $closing = round($account->signedOpeningBalance() + $movement['debit'] - $movement['credit'], 2);

            // Present balances on the normal side of the account type.
            $amount = match ($account->account_type) {
                LedgerAccountType::Asset => $closing,
                default => -$closing,
            };

            if (abs($amount) < 0.01) {
                continue;
            }

            $row = [
                'code' => $account->code,
                'name' => $account->name,
                'amount' => round($amount, 2),
            ];

            if ($account->account_type === LedgerAccountType::Asset) {
                $assets[] = $row;
                $totalAssets += $row['amount'];
            } elseif ($account->account_type === LedgerAccountType::Liability) {
                $liabilities[] = $row;
                $totalLiabilities += $row['amount'];
            } else {
                $equity[] = $row;
                $totalEquity += $row['amount'];
            }
        }

        $pnl = $this->profitAndLoss(null, $asOnDate);
        $retained = $pnl['net_profit'];
        if (abs($retained) >= 0.01) {
            $equity[] = [
                'code' => 'P&L',
                'name' => 'Current period profit/(loss)',
                'amount' => $retained,
            ];
            $totalEquity += $retained;
        }

        return [
            'as_on_date' => $asOnDate,
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'total_assets' => round($totalAssets, 2),
            'total_liabilities' => round($totalLiabilities, 2),
            'total_equity' => round($totalEquity, 2),
            'retained_earnings' => round($retained, 2),
        ];
    }

    /**
     * Control account configured for receivables or payables.
     *
     * @param  'receivable'|'payable'  $type
     */
    public function controlAccount(string $type): ?LedgerAccount
    {
        $settingKey = $type === 'payable' ? 'control_account_payable' : 'control_account_receivable';
        $default = $type === 'payable' ? '2100' : '1200';
        $code = (string) $this->settings->get($settingKey, $default);

        return $this->accounts->findByCode($code);
    }

    /**
     * Reduce a party's ledger lines to open (unsettled) amounts, oldest first.
     *
     * @param  Collection<int, JournalVoucherLine>  $lines
     * @return list<array{date: string, amount: float}>
     */
    protected function applyOnAccountAmounts(Collection $lines, bool $isReceivable): array
    {
        $open = [];
        $onAccount = 0.0;

        $sorted = $lines->sortBy(fn (JournalVoucherLine $line) => $line->journalVoucher?->document_date?->toDateString() ?? '');

        foreach ($sorted as $line) {
            $increase = $isReceivable ? (float) $line->debit : (float) $line->credit;
            $decrease = $isReceivable ? (float) $line->credit : (float) $line->debit;
            $onAccount += $decrease;

            if ($increase <= 0) {
                continue;
            }

            $open[] = [
                'date' => $line->journalVoucher?->document_date?->toDateString() ?? now()->toDateString(),
                'amount' => round($increase, 2),
            ];
        }

        // Settle the oldest outstanding amounts with payments/receipts received on account.
        foreach ($open as $index => $entry) {
            if ($onAccount <= 0) {
                break;
            }

            $applied = min($onAccount, $entry['amount']);
            $open[$index]['amount'] = round($entry['amount'] - $applied, 2);
            $onAccount = round($onAccount - $applied, 2);
        }

        return array_values(array_filter($open, fn (array $entry): bool => $entry['amount'] > 0.005));
    }

    /**
     * @param  list<array{date: string, amount: float}>  $open
     * @return array<string, float>
     */
    protected function bucketise(array $open, string $asOn): array
    {
        $buckets = [
            'bucket_0_30' => 0.0,
            'bucket_31_60' => 0.0,
            'bucket_61_90' => 0.0,
            'bucket_90_plus' => 0.0,
        ];

        foreach ($open as $entry) {
            $age = (int) now()->parse($asOn)->startOfDay()->diffInDays(now()->parse($entry['date'])->startOfDay(), absolute: true);

            $key = match (true) {
                $age <= self::AGEING_BUCKETS[0] => 'bucket_0_30',
                $age <= self::AGEING_BUCKETS[1] => 'bucket_31_60',
                $age <= self::AGEING_BUCKETS[2] => 'bucket_61_90',
                default => 'bucket_90_plus',
            };

            $buckets[$key] = round($buckets[$key] + $entry['amount'], 2);
        }

        return $buckets;
    }

    /**
     * @return array{debit: float, credit: float}
     */
    protected function movementTotals(int $accountId, ?string $fromDate, ?string $toDate): array
    {
        $totals = $this->linesQuery($accountId, $fromDate, $toDate)
            ->selectRaw('COALESCE(SUM(debit),0) as debit_total, COALESCE(SUM(credit),0) as credit_total')
            ->first();

        return [
            'debit' => round((float) ($totals->debit_total ?? 0), 2),
            'credit' => round((float) ($totals->credit_total ?? 0), 2),
        ];
    }

    /**
     * Net debit movement up to a date, used to derive the opening balance of a statement.
     */
    protected function netMovement(int $accountId, ?string $fromDate, ?string $toDate, ?int $partyId, bool $exclusiveTo = false): float
    {
        if ($toDate === null) {
            return 0.0;
        }

        $totals = $this->linesQuery($accountId, $fromDate, $toDate, $partyId, $exclusiveTo)
            ->selectRaw('COALESCE(SUM(debit),0) as debit_total, COALESCE(SUM(credit),0) as credit_total')
            ->first();

        return round((float) ($totals->debit_total ?? 0) - (float) ($totals->credit_total ?? 0), 2);
    }

    /**
     * Posted voucher lines for an account, ordered by voucher date.
     *
     * @return \Illuminate\Database\Eloquent\Builder<JournalVoucherLine>
     */
    protected function postedLines(int $accountId, ?string $fromDate, ?string $toDate, ?int $partyId = null)
    {
        return $this->linesQuery($accountId, $fromDate, $toDate, $partyId)
            ->join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_lines.journal_voucher_id')
            ->orderBy('journal_vouchers.document_date')
            ->orderBy('journal_voucher_lines.id')
            ->select('journal_voucher_lines.*');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<JournalVoucherLine>
     */
    protected function linesQuery(
        int $accountId,
        ?string $fromDate,
        ?string $toDate,
        ?int $partyId = null,
        bool $exclusiveTo = false
    ) {
        return JournalVoucherLine::query()
            ->where('journal_voucher_lines.ledger_account_id', $accountId)
            ->when($partyId, fn ($q) => $q->where('journal_voucher_lines.party_id', $partyId))
            ->whereHas('journalVoucher', function ($q) use ($fromDate, $toDate, $exclusiveTo): void {
                $q->where('status', DocumentStatus::Posted->value);

                if ($fromDate !== null) {
                    $q->whereDate('document_date', '>=', $fromDate);
                }
                if ($toDate !== null) {
                    $q->whereDate('document_date', $exclusiveTo ? '<' : '<=', $toDate);
                }
            });
    }
}
