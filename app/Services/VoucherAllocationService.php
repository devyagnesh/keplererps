<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\PurchaseBillStatus;
use App\Enums\SalesInvoiceStatus;
use App\Enums\VoucherType;
use App\Models\JournalVoucher;
use App\Models\PurchaseBill;
use App\Models\SalesInvoice;
use App\Models\VoucherAllocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Allocate receipt/payment vouchers against open invoices and bills (US-M13-02).
 */
class VoucherAllocationService
{
    /**
     * Open sales invoices for a customer with remaining balance.
     *
     * @return list<array<string, mixed>>
     */
    public function openInvoices(int $partyId): array
    {
        return SalesInvoice::query()
            ->where('customer_id', $partyId)
            ->where('status', SalesInvoiceStatus::Confirmed->value)
            ->orderBy('document_date')
            ->orderBy('id')
            ->get(['id', 'document_no', 'document_date', 'grand_total'])
            ->map(function (SalesInvoice $invoice): ?array {
                $outstanding = $this->outstanding($invoice);
                if ($outstanding <= 0.005) {
                    return null;
                }

                return [
                    'id' => $invoice->id,
                    'type' => SalesInvoice::class,
                    'document_no' => $invoice->document_no,
                    'document_date' => $invoice->document_date?->toDateString(),
                    'grand_total' => (float) $invoice->grand_total,
                    'outstanding' => $outstanding,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Open purchase bills for a supplier with remaining balance.
     *
     * @return list<array<string, mixed>>
     */
    public function openBills(int $partyId): array
    {
        return PurchaseBill::query()
            ->where('supplier_id', $partyId)
            ->where('status', PurchaseBillStatus::Approved->value)
            ->orderBy('document_date')
            ->orderBy('id')
            ->get(['id', 'document_no', 'document_date', 'grand_total'])
            ->map(function (PurchaseBill $bill): ?array {
                $outstanding = $this->outstanding($bill);
                if ($outstanding <= 0.005) {
                    return null;
                }

                return [
                    'id' => $bill->id,
                    'type' => PurchaseBill::class,
                    'document_no' => $bill->document_no,
                    'document_date' => $bill->document_date?->toDateString(),
                    'grand_total' => (float) $bill->grand_total,
                    'outstanding' => $outstanding,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function outstanding(SalesInvoice|PurchaseBill $document): float
    {
        $allocated = (float) VoucherAllocation::query()
            ->where('allocatable_type', $document::class)
            ->where('allocatable_id', $document->id)
            ->sum('amount');

        return round(max(0, (float) $document->grand_total - $allocated), 2);
    }

    /**
     * Replace allocations on a posted receipt/payment voucher.
     *
     * @param  list<array{allocatable_type: string, allocatable_id: int, amount: float, remarks?: string|null}>  $lines
     */
    public function sync(int $voucherId, array $lines): JournalVoucher
    {
        $voucher = JournalVoucher::query()->with('lines')->findOrFail($voucherId);

        if ($voucher->status !== DocumentStatus::Posted) {
            throw ValidationException::withMessages([
                'journal_voucher' => 'Allocate only after the voucher is posted.',
            ]);
        }

        if (! in_array($voucher->voucher_type, [VoucherType::Receipt, VoucherType::Payment], true)) {
            throw ValidationException::withMessages([
                'journal_voucher' => 'Only receipt and payment vouchers can be allocated.',
            ]);
        }

        $voucherAmount = round((float) $voucher->total_debit, 2);
        $totalAllocated = round(collect($lines)->sum(fn (array $line): float => (float) ($line['amount'] ?? 0)), 2);

        if ($totalAllocated - $voucherAmount > 0.01) {
            throw ValidationException::withMessages([
                'allocations' => 'Allocated amount cannot exceed the voucher total.',
            ]);
        }

        return DB::transaction(function () use ($voucher, $lines, $voucherId): JournalVoucher {
            VoucherAllocation::query()->where('journal_voucher_id', $voucherId)->delete();

            foreach ($lines as $line) {
                $amount = round((float) ($line['amount'] ?? 0), 2);
                if ($amount <= 0) {
                    continue;
                }

                $type = (string) ($line['allocatable_type'] ?? '');
                $id = (int) ($line['allocatable_id'] ?? 0);
                $document = $this->resolveDocument($type, $id);
                $outstanding = $this->outstanding($document);

                if ($amount - $outstanding > 0.01) {
                    throw ValidationException::withMessages([
                        'allocations' => "Amount exceeds outstanding on {$document->document_no}.",
                    ]);
                }

                VoucherAllocation::query()->create([
                    'journal_voucher_id' => $voucherId,
                    'allocatable_type' => $document::class,
                    'allocatable_id' => $document->id,
                    'party_id' => $document instanceof SalesInvoice ? $document->customer_id : $document->supplier_id,
                    'amount' => $amount,
                    'remarks' => $line['remarks'] ?? null,
                ]);
            }

            return $voucher->fresh(['allocations']);
        });
    }

    /**
     * @return Collection<int, VoucherAllocation>
     */
    public function forVoucher(int $voucherId): Collection
    {
        return VoucherAllocation::query()
            ->where('journal_voucher_id', $voucherId)
            ->with('allocatable')
            ->get();
    }

    protected function resolveDocument(string $type, int $id): SalesInvoice|PurchaseBill
    {
        return match ($type) {
            SalesInvoice::class, 'sales_invoice' => SalesInvoice::query()->findOrFail($id),
            PurchaseBill::class, 'purchase_bill' => PurchaseBill::query()->findOrFail($id),
            default => throw ValidationException::withMessages([
                'allocations' => 'Unknown document type for allocation.',
            ]),
        };
    }
}
