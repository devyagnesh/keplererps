<?php

namespace App\Services;

use App\Enums\DocumentSeriesType;
use App\Enums\NotificationEvent;
use App\Enums\PartyType;
use App\Enums\PurchaseOrderStatus;
use App\Models\Item;
use App\Models\Party;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockBalance;
use App\Models\Warehouse;
use App\Repositories\Interfaces\PurchaseOrderRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Purchase order business logic (M07).
 */
class PurchaseOrderService
{
    public function __construct(
        protected PurchaseOrderRepositoryInterface $repository,
        protected NumberingService $numbering,
        protected NotificationDispatchService $notifications,
        protected ApprovalRuleService $approvals,
        protected UomConversionService $uom
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): PurchaseOrder
    {
        return $this->repository->findById($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data): PurchaseOrder {
            $lines = $data['items'] ?? [];
            unset($data['items']);

            $this->assertSupplier((int) $data['supplier_id']);
            $this->assertLeafWarehouse((int) $data['warehouse_id']);
            $this->assertDeliveryDate($data);

            $data['document_no'] = $this->numbering->next(DocumentSeriesType::PurchaseOrder);
            $data['status'] = PurchaseOrderStatus::Draft->value;
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();
            $data['subtotal'] = 0;
            $data['tax_total'] = 0;
            $data['grand_total'] = 0;

            $po = $this->repository->create($data);
            $this->syncItems($po, $lines);
            $this->recalculateTotals($po->id);

            return $this->repository->findById($po->id);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($id, $data): PurchaseOrder {
            $po = $this->repository->findById($id);
            $this->assertDraft($po);

            $lines = $data['items'] ?? [];
            unset($data['items'], $data['document_no'], $data['status']);

            if (isset($data['supplier_id'])) {
                $this->assertSupplier((int) $data['supplier_id']);
            }
            if (isset($data['warehouse_id'])) {
                $this->assertLeafWarehouse((int) $data['warehouse_id']);
            }
            $this->assertDeliveryDate(array_merge([
                'document_date' => $po->document_date->toDateString(),
                'expected_delivery_date' => $po->expected_delivery_date->toDateString(),
            ], $data));

            $data['updated_by'] = Auth::id();
            $this->repository->update($id, $data);
            $this->syncItems($po, $lines);
            $this->recalculateTotals($id);

            return $this->repository->findById($id);
        });
    }

    public function delete(int $id): bool
    {
        $po = $this->repository->findById($id);
        $this->assertDraft($po);

        return $this->repository->delete($id);
    }

    /**
     * Approve a draft PO so GRNs can be created against it.
     */
    public function approve(int $id): PurchaseOrder
    {
        return DB::transaction(function () use ($id): PurchaseOrder {
            $po = PurchaseOrder::query()->with('items')->lockForUpdate()->findOrFail($id);

            if ($po->status !== PurchaseOrderStatus::Draft && $po->status !== PurchaseOrderStatus::PendingApproval) {
                throw ValidationException::withMessages([
                    'purchase_order' => 'Only draft or pending purchase orders can be approved.',
                ]);
            }

            if ($po->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Add at least one line before approving.',
                ]);
            }

            $this->approvals->assertCanApprove('purchase_order', [
                'id' => $po->id,
                'grand_total' => (float) $po->grand_total,
            ]);

            $po->forceFill([
                'status' => PurchaseOrderStatus::Approved,
                'approved_at' => now(),
                'approved_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ])->save();

            $this->adjustOnOrder($po, true);

            $approved = $this->repository->findById($id);
            $this->notifications->dispatch(
                NotificationEvent::PurchaseOrderApproved,
                [
                    'document_no' => $approved->document_no,
                    'party_name' => (string) ($approved->supplier?->party_name ?? ''),
                ],
                route('admin.purchase-orders.edit', $approved)
            );

            return $approved;
        });
    }

    /**
     * Mark an approved PO as sent to the supplier.
     */
    public function markSent(int $id): PurchaseOrder
    {
        $po = $this->repository->findById($id);

        if ($po->status !== PurchaseOrderStatus::Approved) {
            throw ValidationException::withMessages([
                'purchase_order' => 'Only approved purchase orders can be marked as sent.',
            ]);
        }

        $po->forceFill([
            'status' => PurchaseOrderStatus::Sent,
            'updated_by' => Auth::id(),
        ])->save();

        return $this->repository->findById($id);
    }

    /**
     * Refresh PO receipt status from line received quantities.
     */
    public function refreshReceiptStatus(PurchaseOrder $po): void
    {
        $po->loadMissing('items');
        $ordered = (float) $po->items->sum('quantity');
        $received = (float) $po->items->sum('received_qty');

        if ($received <= 0) {
            return;
        }

        $status = $received + 0.00005 >= $ordered
            ? PurchaseOrderStatus::Received
            : PurchaseOrderStatus::PartiallyReceived;

        $po->forceFill([
            'status' => $status,
            'updated_by' => Auth::id(),
        ])->save();
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    protected function syncItems(PurchaseOrder $po, array $lines): void
    {
        $po->items()->delete();

        foreach (array_values($lines) as $index => $line) {
            if (empty($line['item_id']) || empty($line['quantity'])) {
                continue;
            }

            $item = Item::query()->findOrFail((int) $line['item_id']);
            if (! $item->is_purchasable) {
                throw ValidationException::withMessages([
                    'items' => "Item {$item->item_code} is not purchasable.",
                ]);
            }

            $qty = round((float) $line['quantity'], 4);
            $uomId = (int) ($line['uom_id'] ?? $item->stock_uom_id);
            $baseQty = $this->safeBaseQty($item, $qty, $uomId);
            $rate = round((float) ($line['rate'] ?? 0), 4);
            $gstRate = round((float) ($line['gst_rate'] ?? $item->gst_rate ?? 0), 2);
            $taxable = round($qty * $rate, 2);
            $tax = round($taxable * ($gstRate / 100), 2);

            PurchaseOrderItem::query()->create([
                'purchase_order_id' => $po->id,
                'item_id' => $item->id,
                'uom_id' => $uomId,
                'quantity' => $qty,
                'base_qty' => $baseQty,
                'rate' => $rate,
                'gst_rate' => $gstRate,
                'tax_amount' => $tax,
                'line_total' => round($taxable + $tax, 2),
                'tolerance_percent' => min(20, max(0, round((float) ($line['tolerance_percent'] ?? 0), 2))),
                'received_qty' => 0,
                'requires_inspection' => (bool) ($line['requires_inspection'] ?? $item->requires_inspection),
                'sort_order' => $index,
            ]);
        }

        if ($po->items()->count() === 0) {
            throw ValidationException::withMessages([
                'items' => 'Add at least one purchase order line.',
            ]);
        }
    }

    protected function safeBaseQty(Item $item, float $qty, int $uomId): float
    {
        try {
            return $this->uom->toStockQty($item, $qty, $uomId);
        } catch (\Throwable) {
            return $qty;
        }
    }

    protected function recalculateTotals(int $id): void
    {
        $po = PurchaseOrder::query()->with('items')->findOrFail($id);
        $subtotal = round((float) $po->items->sum(fn (PurchaseOrderItem $i) => (float) $i->quantity * (float) $i->rate), 2);
        $tax = round((float) $po->items->sum('tax_amount'), 2);

        $po->forceFill([
            'subtotal' => $subtotal,
            'tax_total' => $tax,
            'grand_total' => round($subtotal + $tax, 2),
        ])->save();
    }

    protected function adjustOnOrder(PurchaseOrder $po, bool $increase): void
    {
        foreach ($po->items as $line) {
            $balance = StockBalance::query()->firstOrCreate(
                [
                    'item_id' => $line->item_id,
                    'warehouse_id' => $po->warehouse_id,
                    'batch_key' => 0,
                ],
                [
                    'batch_id' => null,
                    'qty' => 0,
                    'committed_qty' => 0,
                    'on_order_qty' => 0,
                    'value' => 0,
                ]
            );

            $delta = (float) $line->quantity * ($increase ? 1 : -1);
            $balance->forceFill([
                'on_order_qty' => max(0, round((float) $balance->on_order_qty + $delta, 4)),
            ])->save();
        }
    }

    protected function assertDraft(PurchaseOrder $po): void
    {
        if (! $po->status->isEditable()) {
            throw ValidationException::withMessages([
                'purchase_order' => 'Only draft purchase orders can be modified.',
            ]);
        }
    }

    protected function assertSupplier(int $supplierId): void
    {
        $supplier = Party::query()->findOrFail($supplierId);

        if (! in_array($supplier->party_type, [PartyType::Supplier, PartyType::Both], true)) {
            throw ValidationException::withMessages([
                'supplier_id' => 'Selected party must be a supplier.',
            ]);
        }

        if ($supplier->status !== \App\Enums\PartyStatus::Active) {
            throw ValidationException::withMessages([
                'supplier_id' => 'Supplier is inactive.',
            ]);
        }
    }

    protected function assertLeafWarehouse(int $warehouseId): void
    {
        $warehouse = Warehouse::query()->findOrFail($warehouseId);

        if (! $warehouse->is_leaf || ! $warehouse->is_active) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'Delivery warehouse must be an active leaf warehouse.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertDeliveryDate(array $data): void
    {
        if (empty($data['document_date']) || empty($data['expected_delivery_date'])) {
            return;
        }

        if ($data['expected_delivery_date'] < $data['document_date']) {
            throw ValidationException::withMessages([
                'expected_delivery_date' => 'Expected delivery cannot be before the PO date.',
            ]);
        }
    }
}
