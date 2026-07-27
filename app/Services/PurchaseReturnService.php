<?php

namespace App\Services;

use App\Enums\DocumentSeriesType;
use App\Enums\DocumentStatus;
use App\Enums\GrnStatus;
use App\Enums\StockTransactionType;
use App\Enums\TrackingType;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Warehouse;
use App\Repositories\Interfaces\PurchaseReturnRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Purchase return (debit note) business logic — closes the inbound stock loop.
 */
class PurchaseReturnService
{
    public function __construct(
        protected PurchaseReturnRepositoryInterface $repository,
        protected StockLedgerService $ledger,
        protected NumberingService $numbering
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): PurchaseReturn
    {
        return $this->repository->findById($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PurchaseReturn
    {
        return DB::transaction(function () use ($data): PurchaseReturn {
            $lines = $data['items'] ?? [];
            unset($data['items']);

            $grn = $this->loadPostedGrn((int) $data['goods_receipt_id']);
            $this->assertDates($grn, $data);

            $data['document_no'] = $this->numbering->next(DocumentSeriesType::PurchaseReturn);
            $data['supplier_id'] = $grn->supplier_id;
            $data['warehouse_id'] = (int) ($data['warehouse_id'] ?? $grn->warehouse_id);
            $data['status'] = DocumentStatus::Draft->value;
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            $return = $this->repository->create($data);
            $this->syncItems($return, $grn, $lines);
            $this->recalculate($return);

            return $this->repository->findById($return->id);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): PurchaseReturn
    {
        return DB::transaction(function () use ($id, $data): PurchaseReturn {
            $return = $this->repository->findById($id);
            $this->assertDraft($return);

            $lines = $data['items'] ?? [];
            unset($data['items'], $data['document_no'], $data['status'], $data['goods_receipt_id'], $data['supplier_id']);

            $grn = $this->loadPostedGrn((int) $return->goods_receipt_id);
            $this->assertDates($grn, array_merge([
                'document_date' => $return->document_date->toDateString(),
            ], $data));

            $data['updated_by'] = Auth::id();
            $this->repository->update($id, $data);

            $return->refresh();
            $this->syncItems($return, $grn, $lines);
            $this->recalculate($return);

            return $this->repository->findById($id);
        });
    }

    public function delete(int $id): bool
    {
        $return = $this->repository->findById($id);
        $this->assertDraft($return);

        return $this->repository->delete($id);
    }

    /**
     * Post the return: stock leaves the warehouse as a purchase return.
     */
    public function post(int $id): PurchaseReturn
    {
        return DB::transaction(function () use ($id): PurchaseReturn {
            $return = PurchaseReturn::query()->with('items.item')->lockForUpdate()->findOrFail($id);
            $this->assertDraft($return);

            if ($return->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Add at least one return line before posting.',
                ]);
            }

            foreach ($return->items as $line) {
                $this->ledger->post([
                    'item_id' => $line->item_id,
                    'warehouse_id' => $return->warehouse_id,
                    'batch_id' => $line->batch_id,
                    'transaction_type' => StockTransactionType::PurchaseReturn,
                    'posting_at' => $return->document_date->copy()->startOfDay(),
                    'qty_in' => 0,
                    'qty_out' => (float) $line->quantity,
                    'rate' => (float) $line->rate,
                    'source' => $return,
                    'remarks' => $return->document_no,
                ]);
            }

            $return->forceFill([
                'status' => DocumentStatus::Posted,
                'posted_at' => now(),
                'posted_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ])->save();

            return $this->repository->findById($id);
        });
    }

    /**
     * Reverse the ledger effect and cancel the document.
     */
    public function cancel(int $id): PurchaseReturn
    {
        return DB::transaction(function () use ($id): PurchaseReturn {
            $return = PurchaseReturn::query()->lockForUpdate()->findOrFail($id);

            if ($return->status === DocumentStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'purchase_return' => 'This return is already cancelled.',
                ]);
            }

            if ($return->status === DocumentStatus::Posted) {
                $this->ledger->reverseSource($return, 'Cancellation of '.$return->document_no);
            }

            $return->forceFill([
                'status' => DocumentStatus::Cancelled,
                'updated_by' => Auth::id(),
            ])->save();

            return $this->repository->findById($id);
        });
    }

    /**
     * Returnable GRN lines with the quantity still open for return.
     *
     * @return list<array<string, mixed>>
     */
    public function returnableLinesForGrn(int $goodsReceiptId, ?int $ignoreReturnId = null): array
    {
        $grn = $this->loadPostedGrn($goodsReceiptId);
        $returned = $this->repository->returnedQtyByGrnItem($grn->items->pluck('id')->all(), $ignoreReturnId);

        $lines = [];
        foreach ($grn->items as $line) {
            $open = round((float) $line->accepted_qty - (float) ($returned[$line->id] ?? 0), 4);
            if ($open <= 0) {
                continue;
            }

            $lines[] = [
                'goods_receipt_item_id' => $line->id,
                'item_id' => $line->item_id,
                'item_code' => $line->item?->item_code,
                'item_name' => $line->item?->item_name,
                'batch_id' => $line->batch_id,
                'batch_no' => $line->batch_no,
                'accepted_qty' => round((float) $line->accepted_qty, 4),
                'returned_qty' => round((float) ($returned[$line->id] ?? 0), 4),
                'open_qty' => $open,
                'quantity' => $open,
                'rate' => round((float) $line->rate, 4),
                'gst_rate' => round((float) ($line->purchaseOrderItem?->gst_rate ?? 0), 2),
            ];
        }

        if ($lines === []) {
            throw ValidationException::withMessages([
                'goods_receipt_id' => 'Nothing is left to return on this goods receipt.',
            ]);
        }

        return $lines;
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    protected function syncItems(PurchaseReturn $return, GoodsReceipt $grn, array $lines): void
    {
        $return->items()->delete();
        $grnItems = $grn->items->keyBy('id');
        $returned = $this->repository->returnedQtyByGrnItem($grnItems->keys()->all(), $return->id);

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
            if ($quantity <= 0) {
                continue;
            }

            $open = round((float) $grnLine->accepted_qty - (float) ($returned[$grnLine->id] ?? 0), 4);
            if ($quantity - $open > 0.0001) {
                throw ValidationException::withMessages([
                    'items' => sprintf(
                        'Return quantity for %s exceeds the returnable quantity of %s.',
                        $grnLine->item?->item_code ?? 'the item',
                        number_format($open, 4, '.', '')
                    ),
                ]);
            }

            $batchId = $this->resolveBatchId($grnLine, $line);
            $rate = round((float) ($line['rate'] ?? $grnLine->rate), 4);
            $gstRate = round((float) ($line['gst_rate'] ?? $grnLine->purchaseOrderItem?->gst_rate ?? 0), 2);
            $taxable = round($quantity * $rate, 2);
            $tax = round($taxable * $gstRate / 100, 2);

            PurchaseReturnItem::query()->create([
                'purchase_return_id' => $return->id,
                'goods_receipt_item_id' => $grnLine->id,
                'item_id' => $grnLine->item_id,
                'batch_id' => $batchId,
                'quantity' => $quantity,
                'rate' => $rate,
                'gst_rate' => $gstRate,
                'taxable_amount' => $taxable,
                'tax_amount' => $tax,
                'line_total' => round($taxable + $tax, 2),
                'sort_order' => $index,
            ]);
        }

        if ($return->items()->count() === 0) {
            throw ValidationException::withMessages([
                'items' => 'Add at least one purchase return line.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $line
     */
    protected function resolveBatchId(GoodsReceiptItem $grnLine, array $line): ?int
    {
        $batchId = ! empty($line['batch_id']) ? (int) $line['batch_id'] : (int) ($grnLine->batch_id ?? 0);
        if ($batchId > 0) {
            return $batchId;
        }

        $tracking = $grnLine->item?->tracking_type;
        if (in_array($tracking, [TrackingType::Batch, TrackingType::BatchSerial], true)) {
            throw ValidationException::withMessages([
                'items' => 'Batch is required for batch-tracked item '.($grnLine->item?->item_code ?? '').'.',
            ]);
        }

        return null;
    }

    protected function recalculate(PurchaseReturn $return): void
    {
        $items = $return->items()->get();
        $subtotal = round((float) $items->sum(fn (PurchaseReturnItem $line) => (float) $line->taxable_amount), 2);
        $taxTotal = round((float) $items->sum(fn (PurchaseReturnItem $line) => (float) $line->tax_amount), 2);

        $return->forceFill([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'grand_total' => round($subtotal + $taxTotal, 2),
        ])->save();
    }

    protected function loadPostedGrn(int $goodsReceiptId): GoodsReceipt
    {
        $grn = GoodsReceipt::query()
            ->with([
                'items.item:id,item_code,item_name,tracking_type',
                'items.purchaseOrderItem:id,gst_rate,uom_id',
            ])
            ->findOrFail($goodsReceiptId);

        if ($grn->status !== GrnStatus::Posted) {
            throw ValidationException::withMessages([
                'goods_receipt_id' => 'Only posted goods receipts can be returned.',
            ]);
        }

        return $grn;
    }

    /**
     * Leaf warehouses available as the issuing location for a return.
     *
     * @return Collection<int, Warehouse>
     */
    public function issuableWarehouses(): Collection
    {
        return Warehouse::query()
            ->where('is_leaf', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
    }

    protected function assertDraft(PurchaseReturn $return): void
    {
        if ($return->status !== DocumentStatus::Draft) {
            throw ValidationException::withMessages([
                'purchase_return' => 'Only draft purchase returns can be modified.',
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
                'document_date' => 'Return date cannot be before the goods receipt date.',
            ]);
        }
    }
}
