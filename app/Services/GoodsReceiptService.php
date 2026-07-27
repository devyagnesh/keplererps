<?php

namespace App\Services;

use App\Enums\ChargeAllocationBasis;
use App\Enums\DocumentSeriesType;
use App\Enums\GrnStatus;
use App\Enums\StockTransactionType;
use App\Enums\TrackingType;
use App\Models\Batch;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Serial;
use App\Models\StockBalance;
use App\Repositories\Interfaces\GoodsReceiptRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Goods receipt note business logic (M07 / US-M07-03).
 */
class GoodsReceiptService
{
    public function __construct(
        protected GoodsReceiptRepositoryInterface $repository,
        protected PurchaseOrderService $purchaseOrders,
        protected StockLedgerService $ledger,
        protected NumberingService $numbering,
        protected WarehouseResolver $warehouses
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): GoodsReceipt
    {
        return $this->repository->findById($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): GoodsReceipt
    {
        return DB::transaction(function () use ($data): GoodsReceipt {
            $lines = $data['items'] ?? [];
            unset($data['items']);

            $po = PurchaseOrder::query()->with('items')->findOrFail((int) $data['purchase_order_id']);
            $this->assertPoReceivable($po);
            $this->assertInvoiceUnique((int) $po->supplier_id, (string) $data['supplier_invoice_no']);
            $this->assertDates($po, $data);

            $data['document_no'] = $this->numbering->next(DocumentSeriesType::Grn);
            $data['supplier_id'] = $po->supplier_id;
            $data['warehouse_id'] = $po->warehouse_id;
            $data['status'] = GrnStatus::Draft->value;
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            $grn = $this->repository->create($data);
            $this->syncItems($grn, $po, $lines);

            return $this->repository->findById($grn->id);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): GoodsReceipt
    {
        return DB::transaction(function () use ($id, $data): GoodsReceipt {
            $grn = $this->repository->findById($id);
            $this->assertDraft($grn);

            $lines = $data['items'] ?? [];
            unset($data['items'], $data['document_no'], $data['status'], $data['purchase_order_id'], $data['supplier_id'], $data['warehouse_id']);

            $po = PurchaseOrder::query()->with('items')->findOrFail($grn->purchase_order_id);
            $this->assertDates($po, array_merge([
                'document_date' => $grn->document_date->toDateString(),
                'supplier_invoice_date' => $grn->supplier_invoice_date->toDateString(),
            ], $data));

            if (isset($data['supplier_invoice_no']) && $data['supplier_invoice_no'] !== $grn->supplier_invoice_no) {
                $this->assertInvoiceUnique((int) $grn->supplier_id, (string) $data['supplier_invoice_no'], $grn->id);
            }

            $data['updated_by'] = Auth::id();
            $this->repository->update($id, $data);
            $this->syncItems($grn, $po, $lines);

            return $this->repository->findById($id);
        });
    }

    public function delete(int $id): bool
    {
        $grn = $this->repository->findById($id);
        $this->assertDraft($grn);

        return $this->repository->delete($id);
    }

    public function post(int $id): GoodsReceipt
    {
        return DB::transaction(function () use ($id): GoodsReceipt {
            $grn = GoodsReceipt::query()
                ->with(['items.item', 'items.purchaseOrderItem', 'purchaseOrder', 'warehouse'])
                ->lockForUpdate()
                ->findOrFail($id);

            $this->assertDraft($grn);

            if ($grn->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Add at least one receipt line before posting.',
                ]);
            }

            $po = PurchaseOrder::query()->with('items')->lockForUpdate()->findOrFail($grn->purchase_order_id);
            $this->assertPoReceivable($po);

            $quarantine = null;
            $landedRates = $this->allocateLandedCost($grn);

            foreach ($grn->items as $line) {
                $poLine = PurchaseOrderItem::query()->lockForUpdate()->findOrFail($line->purchase_order_item_id);
                $this->assertWithinTolerance($poLine, (float) $line->received_qty);

                $accepted = (float) $line->accepted_qty;
                if ($accepted <= 0) {
                    continue;
                }

                $batchId = $this->resolveBatchId($line);
                $serialId = $this->resolveSerialId($line, $batchId);

                $requiresInspection = (bool) ($poLine->requires_inspection || $line->item?->requires_inspection);
                $warehouseId = (int) $grn->warehouse_id;
                if ($requiresInspection) {
                    $quarantine ??= $this->warehouses->quarantineWarehouse($grn->warehouse?->branch_id);
                    $warehouseId = (int) $quarantine->id;
                }

                $this->ledger->post([
                    'item_id' => $line->item_id,
                    'warehouse_id' => $warehouseId,
                    'batch_id' => $batchId,
                    'serial_id' => $serialId,
                    'transaction_type' => StockTransactionType::GoodsReceipt,
                    'posting_at' => $grn->document_date->copy()->startOfDay(),
                    'qty_in' => $accepted,
                    'qty_out' => 0,
                    'rate' => $landedRates[$line->id] ?? (float) $line->rate,
                    'source' => $grn,
                    'remarks' => $requiresInspection
                        ? $grn->document_no.' (quarantine pending QC)'
                        : $grn->document_no,
                ]);

                $poLine->forceFill([
                    'received_qty' => round((float) $poLine->received_qty + (float) $line->received_qty, 4),
                ])->save();

                $this->reduceOnOrder($po, $poLine->item_id, (float) $line->received_qty);

                if ($batchId !== null && $line->batch_id === null) {
                    $line->forceFill(['batch_id' => $batchId])->save();
                }
            }

            $grn->forceFill([
                'status' => GrnStatus::Posted,
                'posted_at' => now(),
                'posted_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ])->save();

            $po->refresh()->load('items');
            $this->purchaseOrders->refreshReceiptStatus($po);

            $posted = $this->repository->findById($id);
            app(QcInspectionService::class)->createFromGoodsReceipt($posted);

            return $posted;
        });
    }

    /**
     * Prefill pending quantities for a PO (US-M07-03).
     *
     * @return list<array<string, mixed>>
     */
    public function pendingLinesForPo(int $purchaseOrderId): array
    {
        $po = PurchaseOrder::query()->with(['items.item:id,item_code,item_name,tracking_type', 'items.uom:id,code'])->findOrFail($purchaseOrderId);
        $this->assertPoReceivable($po);

        $lines = [];
        foreach ($po->items as $item) {
            $pending = $item->pendingOrderedQty();
            if ($pending <= 0) {
                continue;
            }

            $lines[] = [
                'purchase_order_item_id' => $item->id,
                'item_id' => $item->item_id,
                'item_code' => $item->item?->item_code,
                'item_name' => $item->item?->item_name,
                'ordered_qty' => (float) $item->quantity,
                'received_qty' => (float) $item->received_qty,
                'pending_qty' => $pending,
                'max_receivable' => $item->pendingQty(),
                'tolerance_percent' => (float) $item->tolerance_percent,
                'rate' => (float) $item->rate,
                'tracking_type' => $item->item?->tracking_type?->value,
            ];
        }

        return $lines;
    }

    /**
     * Spread header freight and other charges across accepted lines and persist the
     * resulting landed rate, which is what the stock ledger values the receipt at (M08).
     *
     * @return array<int, float> Landed rate keyed by goods receipt item id.
     */
    protected function allocateLandedCost(GoodsReceipt $grn): array
    {
        $acceptedLines = $grn->items->filter(fn (GoodsReceiptItem $line): bool => (float) $line->accepted_qty > 0);
        $charges = $grn->totalCharges();

        $basis = $grn->charge_allocation_basis instanceof ChargeAllocationBasis
            ? $grn->charge_allocation_basis
            : ChargeAllocationBasis::from((string) ($grn->charge_allocation_basis ?? ChargeAllocationBasis::Value->value));

        $weights = [];
        foreach ($acceptedLines as $line) {
            $weights[$line->id] = $basis === ChargeAllocationBasis::Quantity
                ? round((float) $line->accepted_qty, 4)
                : round((float) $line->accepted_qty * (float) $line->rate, 4);
        }

        $totalWeight = round(array_sum($weights), 4);
        // Fall back to quantity when every line has a zero rate so charges still land somewhere.
        if ($charges > 0 && $totalWeight <= 0) {
            foreach ($acceptedLines as $line) {
                $weights[$line->id] = round((float) $line->accepted_qty, 4);
            }
            $totalWeight = round(array_sum($weights), 4);
        }

        $landedRates = [];
        $allocatedSoFar = 0.0;
        $lastId = $acceptedLines->last()?->id;

        foreach ($acceptedLines as $line) {
            $accepted = round((float) $line->accepted_qty, 4);

            $allocated = 0.0;
            if ($charges > 0 && $totalWeight > 0) {
                $allocated = $line->id === $lastId
                    ? round($charges - $allocatedSoFar, 2)
                    : round($charges * ($weights[$line->id] / $totalWeight), 2);
                $allocatedSoFar = round($allocatedSoFar + $allocated, 2);
            }

            $landedRate = round((float) $line->rate + ($accepted > 0 ? $allocated / $accepted : 0), 4);

            $line->forceFill([
                'allocated_charge' => $allocated,
                'landed_rate' => $landedRate,
            ])->save();

            $landedRates[$line->id] = $landedRate;
        }

        return $landedRates;
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    protected function syncItems(GoodsReceipt $grn, PurchaseOrder $po, array $lines): void
    {
        $grn->items()->delete();
        $poItems = $po->items->keyBy('id');

        foreach (array_values($lines) as $index => $line) {
            if (empty($line['purchase_order_item_id']) || empty($line['received_qty'])) {
                continue;
            }

            $poLine = $poItems->get((int) $line['purchase_order_item_id']);
            if ($poLine === null) {
                throw ValidationException::withMessages([
                    'items' => 'One or more lines do not belong to the selected purchase order.',
                ]);
            }

            $received = round((float) $line['received_qty'], 4);
            $accepted = round((float) ($line['accepted_qty'] ?? $received), 4);
            $rejected = round((float) ($line['rejected_qty'] ?? 0), 4);

            if ($accepted < 0 || $rejected < 0 || $received <= 0) {
                throw ValidationException::withMessages([
                    'items' => 'Received quantity must be greater than zero; accepted/rejected cannot be negative.',
                ]);
            }

            if (abs(($accepted + $rejected) - $received) > 0.0001) {
                throw ValidationException::withMessages([
                    'items' => 'Accepted + rejected must equal received quantity for each line.',
                ]);
            }

            if ($rejected > 0 && empty($line['rejection_reason'])) {
                throw ValidationException::withMessages([
                    'items' => 'Rejection reason is required when rejected quantity is greater than zero.',
                ]);
            }

            $this->assertWithinTolerance($poLine, $received);

            GoodsReceiptItem::query()->create([
                'goods_receipt_id' => $grn->id,
                'purchase_order_item_id' => $poLine->id,
                'item_id' => $poLine->item_id,
                'received_qty' => $received,
                'accepted_qty' => $accepted,
                'rejected_qty' => $rejected,
                'rejection_reason' => $line['rejection_reason'] ?? null,
                'rate' => round((float) ($line['rate'] ?? $poLine->rate), 4),
                'batch_no' => $line['batch_no'] ?? null,
                'mfg_date' => $line['mfg_date'] ?? null,
                'expiry_date' => $line['expiry_date'] ?? null,
                'serial_no' => $line['serial_no'] ?? null,
                'sort_order' => $index,
            ]);
        }

        if ($grn->items()->count() === 0) {
            throw ValidationException::withMessages([
                'items' => 'Add at least one goods receipt line.',
            ]);
        }
    }

    protected function assertWithinTolerance(PurchaseOrderItem $poLine, float $receivingNow): void
    {
        $max = $poLine->pendingQty();
        if ($receivingNow - $max > 0.0001) {
            throw ValidationException::withMessages([
                'items' => sprintf(
                    'Over-receipt blocked for item line #%d. Max receivable including tolerance: %s.',
                    $poLine->id,
                    number_format($max, 4, '.', '')
                ),
            ]);
        }
    }

    protected function resolveBatchId(GoodsReceiptItem $line): ?int
    {
        $item = $line->item;
        $tracking = $item->tracking_type;

        if (! in_array($tracking, [TrackingType::Batch, TrackingType::BatchSerial], true)) {
            return $line->batch_id;
        }

        if ($line->batch_id) {
            return (int) $line->batch_id;
        }

        $batchNo = trim((string) ($line->batch_no ?? ''));
        if ($batchNo === '') {
            throw ValidationException::withMessages([
                'items' => "Batch number is required for item {$item->item_code}.",
            ]);
        }

        $batch = Batch::query()->firstOrCreate(
            ['item_id' => $item->id, 'batch_no' => $batchNo],
            [
                'mfg_date' => $line->mfg_date,
                'expiry_date' => $line->expiry_date,
                'is_active' => true,
            ]
        );

        return (int) $batch->id;
    }

    protected function resolveSerialId(GoodsReceiptItem $line, ?int $batchId): ?int
    {
        $item = $line->item;
        $tracking = $item->tracking_type;

        if (! in_array($tracking, [TrackingType::Serial, TrackingType::BatchSerial], true)) {
            return null;
        }

        $serialNo = trim((string) ($line->serial_no ?? ''));
        if ($serialNo === '') {
            throw ValidationException::withMessages([
                'items' => "Serial number is required for item {$item->item_code}.",
            ]);
        }

        if ((float) $line->accepted_qty != 1.0) {
            throw ValidationException::withMessages([
                'items' => "Serial-tracked item {$item->item_code} must be accepted with quantity 1 per line.",
            ]);
        }

        $serial = Serial::query()->firstOrCreate(
            ['item_id' => $item->id, 'serial_no' => $serialNo],
            [
                'batch_id' => $batchId,
                'status' => 'in_stock',
                'is_active' => true,
            ]
        );

        return (int) $serial->id;
    }

    protected function reduceOnOrder(PurchaseOrder $po, int $itemId, float $qty): void
    {
        $balance = StockBalance::query()
            ->where('item_id', $itemId)
            ->where('warehouse_id', $po->warehouse_id)
            ->where('batch_key', 0)
            ->first();

        if ($balance === null) {
            return;
        }

        $balance->forceFill([
            'on_order_qty' => max(0, round((float) $balance->on_order_qty - $qty, 4)),
        ])->save();
    }

    protected function assertDraft(GoodsReceipt $grn): void
    {
        if ($grn->status !== GrnStatus::Draft) {
            throw ValidationException::withMessages([
                'goods_receipt' => 'Only draft goods receipts can be modified.',
            ]);
        }
    }

    protected function assertPoReceivable(PurchaseOrder $po): void
    {
        if (! $po->status->canReceive()) {
            throw ValidationException::withMessages([
                'purchase_order_id' => 'Purchase order is not open for receipt.',
            ]);
        }
    }

    protected function assertInvoiceUnique(int $supplierId, string $invoiceNo, ?int $ignoreId = null): void
    {
        $exists = GoodsReceipt::query()
            ->where('supplier_id', $supplierId)
            ->where('supplier_invoice_no', $invoiceNo)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'supplier_invoice_no' => 'This supplier invoice number already exists.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertDates(PurchaseOrder $po, array $data): void
    {
        if (! empty($data['document_date']) && $data['document_date'] < $po->document_date->toDateString()) {
            throw ValidationException::withMessages([
                'document_date' => 'GRN date cannot be before the purchase order date.',
            ]);
        }

        if (! empty($data['supplier_invoice_date']) && $data['supplier_invoice_date'] > now()->toDateString()) {
            throw ValidationException::withMessages([
                'supplier_invoice_date' => 'Supplier invoice date cannot be in the future.',
            ]);
        }
    }
}
