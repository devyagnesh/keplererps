<?php

namespace App\Services;

use App\Enums\VoucherType;
use App\Models\Company;
use App\Models\JournalVoucher;
use App\Models\LedgerAccount;
use App\Models\PurchaseBill;
use App\Models\SalaryRun;
use App\Models\SalesInvoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Auto-posts source documents into the general ledger using control accounts (M13).
 *
 * Posting is skipped (and logged) when the chart of accounts or control-account settings
 * are not configured yet, so inventory and sales flows keep working on a fresh install.
 */
class AccountingPostingService
{
    /**
     * Control-account settings resolved from `system_settings`, keyed by setting key.
     *
     * @var array<string, string>
     */
    protected const CONTROL_ACCOUNT_DEFAULTS = [
        'control_account_receivable' => '1200',
        'control_account_payable' => '2100',
        'control_account_sales' => '4100',
        'control_account_purchase' => '5100',
        'control_account_output_cgst' => '2210',
        'control_account_output_sgst' => '2220',
        'control_account_output_igst' => '2230',
        'control_account_input_cgst' => '1310',
        'control_account_input_sgst' => '1320',
        'control_account_input_igst' => '1330',
        'control_account_round_off' => '5900',
        'control_account_salary_expense' => '5300',
        'control_account_salary_payable' => '2300',
    ];

    public function __construct(
        protected JournalVoucherService $vouchers,
        protected LedgerAccountService $accounts,
        protected SystemSettingService $settings
    ) {}

    /**
     * Post a confirmed sales invoice: debit receivable, credit sales and output GST.
     */
    public function postSalesInvoice(SalesInvoice $invoice): ?JournalVoucher
    {
        $invoice->loadMissing(['items', 'customer:id,party_code,party_name']);

        $taxable = round((float) $invoice->items->sum(fn ($line) => (float) $line->taxable_amount), 2);
        $cgst = round((float) $invoice->items->sum(fn ($line) => (float) $line->cgst_amount), 2);
        $sgst = round((float) $invoice->items->sum(fn ($line) => (float) $line->sgst_amount), 2);
        $igst = round((float) $invoice->items->sum(fn ($line) => (float) $line->igst_amount), 2);
        $grandTotal = round((float) $invoice->grand_total, 2);

        $lines = [
            $this->debit('control_account_receivable', $grandTotal, $invoice->customer_id, 'Invoice '.$invoice->document_no),
            $this->credit('control_account_sales', $taxable),
            $this->credit('control_account_output_cgst', $cgst),
            $this->credit('control_account_output_sgst', $sgst),
            $this->credit('control_account_output_igst', $igst),
        ];

        return $this->post(
            voucherType: VoucherType::Sales,
            documentDate: $invoice->document_date->toDateString(),
            lines: $lines,
            source: $invoice,
            narration: 'Sales invoice '.$invoice->document_no.' — '.($invoice->customer?->party_name ?? ''),
            referenceNo: $invoice->document_no
        );
    }

    /**
     * Post an approved purchase bill: debit purchases and input GST, credit payable.
     */
    public function postPurchaseBill(PurchaseBill $bill): ?JournalVoucher
    {
        $bill->loadMissing(['items', 'supplier:id,party_code,party_name,billing_state_id']);

        $taxable = round((float) $bill->items->sum(fn ($line) => (float) $line->taxable_amount), 2);
        $taxTotal = round((float) $bill->items->sum(fn ($line) => (float) $line->tax_amount), 2);
        $otherCharges = round((float) $bill->other_charges, 2);
        $grandTotal = round((float) $bill->grand_total, 2);

        $lines = [
            $this->debit('control_account_purchase', round($taxable + $otherCharges, 2)),
        ];

        // Purchase bill lines carry a single tax bucket, so split it by place of supply.
        if ($this->isIntraState($bill)) {
            $half = round($taxTotal / 2, 2);
            $lines[] = $this->debit('control_account_input_cgst', $half);
            $lines[] = $this->debit('control_account_input_sgst', round($taxTotal - $half, 2));
        } else {
            $lines[] = $this->debit('control_account_input_igst', $taxTotal);
        }

        $lines[] = $this->credit('control_account_payable', $grandTotal, $bill->supplier_id, 'Bill '.$bill->document_no);

        return $this->post(
            voucherType: VoucherType::Purchase,
            documentDate: $bill->document_date->toDateString(),
            lines: $lines,
            source: $bill,
            narration: 'Purchase bill '.$bill->document_no.' — '.($bill->supplier?->party_name ?? ''),
            referenceNo: $bill->supplier_bill_no
        );
    }

    /**
     * Post a salary run: debit salary expense, credit salary payable.
     */
    public function postSalaryRun(SalaryRun $run): ?JournalVoucher
    {
        $gross = round((float) $run->gross_total, 2);
        $deductions = round((float) $run->deduction_total, 2);
        $net = round((float) $run->net_total, 2);

        $lines = [
            $this->debit('control_account_salary_expense', $gross, narration: 'Payroll '.$run->periodLabel()),
            $this->credit('control_account_salary_payable', $net, narration: 'Net payable '.$run->periodLabel()),
        ];

        // Recoveries such as advances reduce what is owed, so they go back to the expense account.
        if ($deductions > 0) {
            $lines[] = $this->credit('control_account_salary_expense', $deductions, narration: 'Deductions '.$run->periodLabel());
        }

        return $this->post(
            voucherType: VoucherType::Journal,
            documentDate: $run->payment_date->toDateString(),
            lines: $lines,
            source: $run,
            narration: 'Salary run '.$run->document_no.' — '.$run->periodLabel(),
            referenceNo: $run->document_no
        );
    }

    /**
     * Cancel the voucher generated from a source document.
     */
    public function reverse(Model $document): ?JournalVoucher
    {
        try {
            return $this->vouchers->cancelForSource($document);
        } catch (ValidationException $e) {
            Log::warning('Could not cancel the accounting voucher for a document.', [
                'document' => $document::class,
                'document_id' => $document->getKey(),
                'reason' => collect($e->errors())->flatten()->first(),
            ]);

            return null;
        }
    }

    /**
     * @param  list<array<string, mixed>|null>  $lines
     */
    protected function post(
        VoucherType $voucherType,
        string $documentDate,
        array $lines,
        Model $source,
        string $narration,
        ?string $referenceNo
    ): ?JournalVoucher {
        $lines = array_values(array_filter($lines));

        if ($lines === []) {
            Log::info('Skipped accounting post: control accounts are not configured.', [
                'document' => $source::class,
                'document_id' => $source->getKey(),
            ]);

            return null;
        }

        $balancing = $this->balancingLine($lines);
        if ($balancing !== null) {
            $lines[] = $balancing;
        }

        try {
            return $this->vouchers->postSystemVoucher(
                voucherType: $voucherType,
                documentDate: $documentDate,
                lines: $lines,
                source: $source,
                narration: $narration,
                referenceNo: $referenceNo
            );
        } catch (ValidationException $e) {
            Log::warning('Skipped accounting post.', [
                'document' => $source::class,
                'document_id' => $source->getKey(),
                'reason' => collect($e->errors())->flatten()->first(),
            ]);

            return null;
        }
    }

    /**
     * Round-off line that squares the voucher when document rounding leaves a residue.
     *
     * @param  list<array<string, mixed>>  $lines
     * @return array<string, mixed>|null
     */
    protected function balancingLine(array $lines): ?array
    {
        $debit = round(array_sum(array_map(fn (array $line): float => (float) ($line['debit'] ?? 0), $lines)), 2);
        $credit = round(array_sum(array_map(fn (array $line): float => (float) ($line['credit'] ?? 0), $lines)), 2);
        $difference = round($debit - $credit, 2);

        if (abs($difference) < 0.005) {
            return null;
        }

        return $difference > 0
            ? $this->credit('control_account_round_off', $difference)
            : $this->debit('control_account_round_off', abs($difference));
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function debit(string $settingKey, float $amount, ?int $partyId = null, ?string $narration = null): ?array
    {
        return $this->line($settingKey, debit: $amount, credit: 0.0, partyId: $partyId, narration: $narration);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function credit(string $settingKey, float $amount, ?int $partyId = null, ?string $narration = null): ?array
    {
        return $this->line($settingKey, debit: 0.0, credit: $amount, partyId: $partyId, narration: $narration);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function line(string $settingKey, float $debit, float $credit, ?int $partyId, ?string $narration): ?array
    {
        if (round($debit, 2) <= 0 && round($credit, 2) <= 0) {
            return null;
        }

        $account = $this->resolveAccount($settingKey);
        if ($account === null) {
            return null;
        }

        return [
            'ledger_account_id' => $account->id,
            'party_id' => $partyId,
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
            'narration' => $narration,
        ];
    }

    protected function resolveAccount(string $settingKey): ?LedgerAccount
    {
        $code = (string) $this->settings->get($settingKey, self::CONTROL_ACCOUNT_DEFAULTS[$settingKey] ?? '');
        if ($code === '') {
            return null;
        }

        return $this->accounts->findByCode($code);
    }

    /**
     * Intra-state when the supplier's billing state matches the company state.
     */
    protected function isIntraState(PurchaseBill $bill): bool
    {
        $companyStateId = (int) (Company::query()->value('state_id') ?? 0);
        $supplierStateId = (int) ($bill->supplier?->billing_state_id ?? 0);

        if ($companyStateId === 0 || $supplierStateId === 0) {
            return true;
        }

        return $companyStateId === $supplierStateId;
    }
}
