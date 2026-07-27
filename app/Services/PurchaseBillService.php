<?php

namespace App\Services;

use App\Enums\DocumentSeriesType;
use App\Enums\GrnStatus;
use App\Enums\MatchStatus;
use App\Enums\PurchaseBillStatus;
use App\Models\GoodsReceipt;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Repositories\Interfaces\PurchaseBillRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Supplier purchase bill business logic with three-way match (M07 / US-M07-04).
 */
class PurchaseBillService
{
    /**
     * Fallback tolerances when system settings are absent.
     */
    public const DEFAULT_RATE_TOLERANCE_PERCENT = 1.0;

    public const DEFAULT_QTY_TOLERANCE_PERCENT = 0.0;

    public function __construct(
        protected PurchaseBillRepositoryInterface $repository,
        protected NumberingService $numbering,
        protected SystemSettingService $settings,
        protected AccountingPostingService $accounting
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): PurchaseBill
    {
        return $this->repository->findById($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PurchaseBill
    {
        return DB::transaction(function () use ($data): PurchaseBill {
            $lines = $data['items'] ?? [];
            unset($data['items']);

            $grn = $this->loadBillableGrn((int) $data['goods_receipt_id']);
            $this->assertSupplierBillUnique((int) $grn->supplier_id, (string) $data['supplier_bill_no']);
            $this->assertDates($grn, $data);

            $data['document_no'] = $this->numbering->next(DocumentSeriesType::PurchaseBill);
            $data['supplier_id'] = $grn->supplier_id;
            $data['purchase_order_id'] = $grn->purchase_order_id;
            $data['status'] = PurchaseBillStatus::Draft->value;
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            $bill = $this->repository->create($data);
            $this->syncItems($bill, $grn, $lines);
            $this->recalculate($bill);

            return $this->repository->findById($bill->id);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): PurchaseBill
    {
        return DB::transaction(function () use ($id, $data): PurchaseBill {
            $bill = $this->repository->findById($id);
            $this->assertDraft($bill);

            $lines = $data['items'] ?? [];
            unset(
                $data['items'],
                $data['document_no'],
                $data['status'],
                $data['goods_receipt_id'],
                $data['purchase_order_id'],
                $data['supplier_id'],
                $data['match_status'],
            );

            $grn = $this->loadBillableGrn((int) $bill->goods_receipt_id);

            if (isset($data['supplier_bill_no']) && $data['supplier_bill_no'] !== $bill->supplier_bill_no) {
                $this->assertSupplierBillUnique((int) $bill->supplier_id, (string) $data['supplier_bill_no'], $bill->id);
            }

            $this->assertDates($grn, array_merge([
                'document_date' => $bill->document_date->toDateString(),
                'supplier_bill_date' => $bill->supplier_bill_date->toDateString(),
            ], $data));

            $data['updated_by'] = Auth::id();
            $this->repository->update($id, $data);

            $bill->refresh();
            $this->syncItems($bill, $grn, $lines);
            $this->recalculate($bill);

            return $this->repository->findById($id);
        });
    }

    public function delete(int $id): bool
    {
        $bill = $this->repository->findById($id);
        $this->assertDraft($bill);

        return $this->repository->delete($id);
    }

    /**
     * Approve a bill, blocking mismatches outside tolerance without an override.
     */
    public function approve(int $id, ?string $mismatchReason = null): PurchaseBill
    {
        return DB::transaction(function () use ($id, $mismatchReason): PurchaseBill {
            $bill = PurchaseBill::query()->with('items')->lockForUpdate()->findOrFail($id);
            $this->assertDraft($bill);

            if ($bill->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Add at least one bill line before approving.',
                ]);
            }

            $this->recalculate($bill);
            $bill->refresh();

            if (! $bill->match_status->isMatched()) {
                $this->assertMismatchOverrideAllowed($mismatchReason);
            }

            $bill->forceFill([
                'status' => PurchaseBillStatus::Approved,
                'mismatch_reason' => $bill->match_status->isMatched() ? null : $mismatchReason,
                'approved_at' => now(),
                'approved_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ])->save();

            $this->accounting->postPurchaseBill($bill->refresh());

            return $this->repository->findById($id);
        });
    }

    public function cancel(int $id): PurchaseBill
    {
        return DB::transaction(function () use ($id): PurchaseBill {
            $bill = $this->repository->findById($id);

            if ($bill->status === PurchaseBillStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'purchase_bill' => 'This bill is already cancelled.',
                ]);
            }

            $bill->forceFill([
                'status' => PurchaseBillStatus::Cancelled,
                'updated_by' => Auth::id(),
            ])->save();

            $this->accounting->reverse($bill);

            return $this->repository->findById($id);
        });
    }

    /**
     * Prefill billable lines from a posted GRN (US-M07-04).
     *
     * @return list<array<string, mixed>>
     */
    public function billableLinesForGrn(int $goodsReceiptId): array
    {
        $grn = $this->loadBillableGrn($goodsReceiptId);

        $lines = [];
        foreach ($grn->items as $line) {
            $accepted = round((float) $line->accepted_qty, 4);
            if ($accepted <= 0) {
                continue;
            }

            $poLine = $line->purchaseOrderItem;

            $lines[] = [
                'goods_receipt_item_id' => $line->id,
                'purchase_order_item_id' => $line->purchase_order_item_id,
                'item_id' => $line->item_id,
                'item_code' => $line->item?->item_code,
                'item_name' => $line->item?->item_name,
                'uom_id' => $poLine?->uom_id,
                'uom_code' => $poLine?->uom?->code,
                'grn_qty' => $accepted,
                'quantity' => $accepted,
                'po_rate' => round((float) ($poLine?->rate ?? $line->rate), 4),
                'rate' => round((float) ($poLine?->rate ?? $line->rate), 4),
                'gst_rate' => round((float) ($poLine?->gst_rate ?? 0), 2),
            ];
        }

        if ($lines === []) {
            throw ValidationException::withMessages([
                'goods_receipt_id' => 'This goods receipt has no accepted quantity to bill.',
            ]);
        }

        return $lines;
    }

    /**
     * Rate tolerance percentage allowed between PO rate and billed rate.
     */
    public function rateTolerancePercent(): float
    {
        return (float) $this->settings->get('purchase_bill_rate_tolerance_percent', self::DEFAULT_RATE_TOLERANCE_PERCENT);
    }

    /**
     * Quantity tolerance percentage allowed between GRN accepted qty and billed qty.
     */
    public function qtyTolerancePercent(): float
    {
        return (float) $this->settings->get('purchase_bill_qty_tolerance_percent', self::DEFAULT_QTY_TOLERANCE_PERCENT);
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    protected function syncItems(PurchaseBill $bill, GoodsReceipt $grn, array $lines): void
    {
        $bill->items()->delete();
        $grnItems = $grn->items->keyBy('id');

        foreach (array_values($lines) as $index => $line) {
            if (empty($line['goods_receipt_item_id'])) {
                continue;
            }

            $grnLine = $grnItems->get((int) $line['goods_receipt_item_id']);
            if ($grnLine === null) {
                throw ValidationException::withMessages([
                    'items' => 'One or more lines do not belong to the selected goods receipt.',
                ]);
            }

            $quantity = round((float) ($line['quantity'] ?? 0), 4);
            $rate = round((float) ($line['rate'] ?? 0), 4);

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    'items' => 'Billed quantity must be greater than zero.',
                ]);
            }

            $poLine = $grnLine->purchaseOrderItem;
            $poRate = round((float) ($poLine?->rate ?? $grnLine->rate), 4);
            $grnQty = round((float) $grnLine->accepted_qty, 4);
            $gstRate = round((float) ($line['gst_rate'] ?? $poLine?->gst_rate ?? 0), 2);

            $taxable = round($quantity * $rate, 2);
            $tax = round($taxable * $gstRate / 100, 2);

            PurchaseBillItem::query()->create([
                'purchase_bill_id' => $bill->id,
                'goods_receipt_item_id' => $grnLine->id,
                'purchase_order_item_id' => $grnLine->purchase_order_item_id,
                'item_id' => $grnLine->item_id,
                'uom_id' => (int) ($line['uom_id'] ?? $poLine?->uom_id),
                'quantity' => $quantity,
                'rate' => $rate,
                'gst_rate' => $gstRate,
                'taxable_amount' => $taxable,
                'tax_amount' => $tax,
                'line_total' => round($taxable + $tax, 2),
                'po_rate' => $poRate,
                'grn_qty' => $grnQty,
                'rate_variance_percent' => $this->variancePercent($poRate, $rate),
                'qty_variance' => round($quantity - $grnQty, 4),
                'match_status' => $this->matchLine($poRate, $rate, $grnQty, $quantity)->value,
                'sort_order' => $index,
            ]);
        }

        if ($bill->items()->count() === 0) {
            throw ValidationException::withMessages([
                'items' => 'Add at least one purchase bill line.',
            ]);
        }
    }

    /**
     * Recompute header totals and the aggregate match status.
     */
    protected function recalculate(PurchaseBill $bill): void
    {
        $items = $bill->items()->get();

        $subtotal = round((float) $items->sum(fn (PurchaseBillItem $line) => (float) $line->taxable_amount), 2);
        $taxTotal = round((float) $items->sum(fn (PurchaseBillItem $line) => (float) $line->tax_amount), 2);
        $otherCharges = round((float) $bill->other_charges, 2);
        $gross = round($subtotal + $taxTotal + $otherCharges, 2);
        $grandTotal = round($gross);

        $rateMismatch = $items->contains(
            fn (PurchaseBillItem $line) => in_array(
                $line->match_status,
                [MatchStatus::RateMismatch, MatchStatus::RateAndQtyMismatch],
                true
            )
        );
        $qtyMismatch = $items->contains(
            fn (PurchaseBillItem $line) => in_array(
                $line->match_status,
                [MatchStatus::QtyMismatch, MatchStatus::RateAndQtyMismatch],
                true
            )
        );

        $bill->forceFill([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'round_off' => round($grandTotal - $gross, 2),
            'grand_total' => $grandTotal,
            'match_status' => MatchStatus::fromFlags($rateMismatch, $qtyMismatch),
        ])->save();
    }

    /**
     * Compare PO rate and GRN quantity against the billed values.
     */
    protected function matchLine(float $poRate, float $billRate, float $grnQty, float $billQty): MatchStatus
    {
        $rateMismatch = abs($this->variancePercent($poRate, $billRate)) - $this->rateTolerancePercent() > 0.0001;

        $qtyTolerance = abs($grnQty) * $this->qtyTolerancePercent() / 100;
        $qtyMismatch = abs($billQty - $grnQty) - $qtyTolerance > 0.0001;

        return MatchStatus::fromFlags($rateMismatch, $qtyMismatch);
    }

    /**
     * Signed percentage difference of the billed rate against the PO rate.
     */
    protected function variancePercent(float $poRate, float $billRate): float
    {
        if ($poRate <= 0.0) {
            return $billRate > 0.0 ? 100.0 : 0.0;
        }

        return round((($billRate - $poRate) / $poRate) * 100, 4);
    }

    protected function assertMismatchOverrideAllowed(?string $reason): void
    {
        if (! Auth::user()?->hasPermissionTo('purchase_bill.approve_mismatch')) {
            throw ValidationException::withMessages([
                'purchase_bill' => 'Bill is outside match tolerance. You need the mismatch approval permission.',
            ]);
        }

        if (trim((string) $reason) === '') {
            throw ValidationException::withMessages([
                'mismatch_reason' => 'A reason is required to approve a bill outside match tolerance.',
            ]);
        }
    }

    protected function loadBillableGrn(int $goodsReceiptId): GoodsReceipt
    {
        $grn = GoodsReceipt::query()
            ->with([
                'items.item:id,item_code,item_name',
                'items.purchaseOrderItem.uom:id,code',
            ])
            ->findOrFail($goodsReceiptId);

        if ($grn->status !== GrnStatus::Posted) {
            throw ValidationException::withMessages([
                'goods_receipt_id' => 'Only posted goods receipts can be billed.',
            ]);
        }

        return $grn;
    }

    protected function assertDraft(PurchaseBill $bill): void
    {
        if (! $bill->status->isEditable()) {
            throw ValidationException::withMessages([
                'purchase_bill' => 'Only draft purchase bills can be modified.',
            ]);
        }
    }

    protected function assertSupplierBillUnique(int $supplierId, string $billNo, ?int $ignoreId = null): void
    {
        $exists = PurchaseBill::query()
            ->where('supplier_id', $supplierId)
            ->where('supplier_bill_no', $billNo)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'supplier_bill_no' => 'This supplier bill number already exists for the supplier.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertDates(GoodsReceipt $grn, array $data): void
    {
        if (! empty($data['document_date']) && $data['document_date'] < $grn->document_date->toDateString()) {
            throw ValidationException::withMessages([
                'document_date' => 'Bill date cannot be before the goods receipt date.',
            ]);
        }

        if (! empty($data['supplier_bill_date']) && $data['supplier_bill_date'] > now()->toDateString()) {
            throw ValidationException::withMessages([
                'supplier_bill_date' => 'Supplier bill date cannot be in the future.',
            ]);
        }
    }
}
