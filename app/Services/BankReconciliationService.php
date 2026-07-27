<?php

namespace App\Services;

use App\Models\JournalVoucherLine;
use App\Models\LedgerAccount;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Bank reconciliation against cash & bank ledger lines (M13 / BR-39).
 */
class BankReconciliationService
{
    /**
     * Cash & Bank group ledger accounts.
     *
     * @return Collection<int, LedgerAccount>
     */
    public function bankAccounts(): Collection
    {
        return LedgerAccount::query()
            ->where('is_active', true)
            ->where(function ($q): void {
                $q->where('account_group', 'Cash & Bank')
                    ->orWhere('code', 'like', '11%')
                    ->orWhere('code', '1000');
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'account_group']);
    }

    /**
     * Unreconciled (or all) voucher lines for a bank/cash account.
     *
     * @return Collection<int, JournalVoucherLine>
     */
    public function lines(int $ledgerAccountId, string $fromDate, string $toDate, bool $unreconciledOnly = true)
    {
        return JournalVoucherLine::query()
            ->with(['voucher:id,document_no,document_date,voucher_type,status', 'ledgerAccount:id,code,name'])
            ->where('ledger_account_id', $ledgerAccountId)
            ->whereHas('voucher', function ($q) use ($fromDate, $toDate): void {
                $q->whereDate('document_date', '>=', $fromDate)
                    ->whereDate('document_date', '<=', $toDate)
                    ->where('status', '!=', 'draft');
            })
            ->when($unreconciledOnly, fn ($q) => $q->whereNull('reconciled_at'))
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  list<int>  $lineIds
     */
    public function reconcile(array $lineIds, ?string $bankDate = null): int
    {
        if ($lineIds === []) {
            throw ValidationException::withMessages(['lines' => 'Select at least one line to reconcile.']);
        }

        $date = $bankDate ?: now()->toDateString();
        $count = 0;

        foreach ($lineIds as $lineId) {
            $line = JournalVoucherLine::query()->find((int) $lineId);
            if ($line === null || $line->reconciled_at !== null) {
                continue;
            }

            $line->forceFill([
                'reconciled_at' => now(),
                'bank_date' => $date,
                'reconciled_by' => Auth::id(),
            ])->save();
            $count++;
        }

        return $count;
    }

    /**
     * @param  list<int>  $lineIds
     */
    public function unreconcile(array $lineIds): int
    {
        return JournalVoucherLine::query()
            ->whereIn('id', $lineIds)
            ->whereNotNull('reconciled_at')
            ->update([
                'reconciled_at' => null,
                'bank_date' => null,
                'reconciled_by' => null,
            ]);
    }
}
