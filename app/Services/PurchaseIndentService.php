<?php

namespace App\Services;

use App\Enums\DocumentSeriesType;
use App\Enums\PurchaseIndentStatus;
use App\Models\Item;
use App\Models\PurchaseIndent;
use App\Models\PurchaseIndentItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Purchase indent workflow from suggestions (US-M07-01).
 */
class PurchaseIndentService
{
    public function __construct(
        protected NumberingService $numbering,
        protected PurchaseSuggestionService $suggestions,
        protected UomConversionService $uom,
        protected PurchaseOrderService $purchaseOrders
    ) {}

    /**
     * @return \Illuminate\Support\Collection<int, PurchaseIndent>
     */
    public function all()
    {
        return PurchaseIndent::query()
            ->with(['warehouse:id,code,name', 'items.item:id,item_code,item_name'])
            ->latest('id')
            ->limit(200)
            ->get();
    }

    public function find(int $id): PurchaseIndent
    {
        return PurchaseIndent::query()
            ->with(['warehouse', 'items.item', 'items.uom'])
            ->findOrFail($id);
    }

    /**
     * Create an indent from the current suggestion set (or explicit lines).
     *
     * @param  array<string, mixed>  $data
     */
    public function createFromSuggestions(array $data): PurchaseIndent
    {
        $warehouseId = (int) ($data['warehouse_id'] ?? 0);
        if ($warehouseId <= 0) {
            throw ValidationException::withMessages(['warehouse_id' => 'Warehouse is required.']);
        }

        $lines = $data['items'] ?? null;
        if (! is_array($lines) || $lines === []) {
            $lines = collect($this->suggestions->suggestions($warehouseId))
                ->map(fn (array $row): array => [
                    'item_id' => $row['item_id'],
                    'uom_id' => $row['stock_uom_id'] ?? $row['uom_id'] ?? Item::query()->find($row['item_id'])?->stock_uom_id,
                    'quantity' => $row['suggested_qty'] ?? $row['shortage_qty'] ?? 0,
                    'source' => $row['source'] ?? 'reorder',
                ])
                ->filter(fn (array $row): bool => (float) ($row['quantity'] ?? 0) > 0 && ! empty($row['uom_id']))
                ->values()
                ->all();
        }

        if ($lines === []) {
            throw ValidationException::withMessages(['items' => 'No purchasable suggestions to indent.']);
        }

        return DB::transaction(function () use ($data, $warehouseId, $lines): PurchaseIndent {
            $indent = PurchaseIndent::query()->create([
                'document_no' => $this->numbering->next(DocumentSeriesType::PurchaseIndent),
                'document_date' => $data['document_date'] ?? now()->toDateString(),
                'warehouse_id' => $warehouseId,
                'status' => PurchaseIndentStatus::Draft,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $sort = 0;
            foreach ($lines as $line) {
                $item = Item::query()->findOrFail((int) $line['item_id']);
                $qty = round((float) $line['quantity'], 4);
                $uomId = (int) ($line['uom_id'] ?? $item->stock_uom_id);
                $baseQty = $this->safeBaseQty($item, $qty, $uomId);
                PurchaseIndentItem::query()->create([
                    'purchase_indent_id' => $indent->id,
                    'item_id' => $item->id,
                    'uom_id' => $uomId,
                    'quantity' => $qty,
                    'base_qty' => $baseQty,
                    'ordered_qty' => 0,
                    'source' => (string) ($line['source'] ?? 'manual'),
                    'sort_order' => $sort++,
                ]);
            }

            return $this->find($indent->id);
        });
    }

    /**
     * Convert an approved indent into a draft PO for a supplier.
     *
     * @param  array<string, mixed>  $data
     */
    public function convertToPurchaseOrder(int $indentId, array $data): \App\Models\PurchaseOrder
    {
        $indent = $this->find($indentId);
        if (! in_array($indent->status, [PurchaseIndentStatus::Approved, PurchaseIndentStatus::PartiallyOrdered], true)) {
            throw ValidationException::withMessages(['indent' => 'Only approved indents can convert to a PO.']);
        }

        $items = [];
        foreach ($indent->items as $line) {
            $pending = $line->pendingQty();
            if ($pending <= 0) {
                continue;
            }
            $items[] = [
                'item_id' => $line->item_id,
                'uom_id' => $line->uom_id,
                'quantity' => $pending,
                'rate' => (float) ($data['rates'][$line->id] ?? $data['rates'][(string) $line->id] ?? 0),
                'gst_rate' => (float) ($data['gst_rate'] ?? $line->item?->gst_rate ?? 18),
            ];
        }

        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'No pending indent quantity to order.']);
        }

        return DB::transaction(function () use ($indent, $data, $items): \App\Models\PurchaseOrder {
            $po = $this->purchaseOrders->create([
                'document_date' => $data['document_date'] ?? now()->toDateString(),
                'expected_delivery_date' => $data['expected_delivery_date'] ?? now()->addDays(7)->toDateString(),
                'supplier_id' => (int) $data['supplier_id'],
                'warehouse_id' => $indent->warehouse_id,
                'purchase_indent_id' => $indent->id,
                'remarks' => $data['remarks'] ?? 'From indent '.$indent->document_no,
                'items' => $items,
            ]);

            foreach ($indent->items as $line) {
                $pending = $line->pendingQty();
                if ($pending <= 0) {
                    continue;
                }
                $line->forceFill([
                    'ordered_qty' => round((float) $line->ordered_qty + $pending, 4),
                ])->save();
            }

            $indent->refresh()->load('items');
            $remaining = $indent->items->sum(fn ($l) => $l->pendingQty());
            $indent->forceFill([
                'status' => $remaining > 0.0001
                    ? PurchaseIndentStatus::PartiallyOrdered
                    : PurchaseIndentStatus::Ordered,
                'updated_by' => Auth::id(),
            ])->save();

            return $po;
        });
    }

    protected function safeBaseQty(Item $item, float $qty, int $uomId): float
    {
        try {
            return $this->uom->toStockQty($item, $qty, $uomId);
        } catch (\Throwable) {
            return $qty;
        }
    }

    public function approve(int $id): PurchaseIndent
    {
        return DB::transaction(function () use ($id): PurchaseIndent {
            $indent = PurchaseIndent::query()->with('items')->lockForUpdate()->findOrFail($id);
            if ($indent->status !== PurchaseIndentStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Only draft indents can be approved.']);
            }
            if ($indent->items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Add at least one line before approving.']);
            }

            $indent->forceFill([
                'status' => PurchaseIndentStatus::Approved,
                'approved_at' => now(),
                'approved_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ])->save();

            return $this->find($id);
        });
    }

    public function cancel(int $id): PurchaseIndent
    {
        $indent = PurchaseIndent::query()->findOrFail($id);
        if (in_array($indent->status, [PurchaseIndentStatus::Ordered, PurchaseIndentStatus::Closed], true)) {
            throw ValidationException::withMessages(['status' => 'Ordered or closed indents cannot be cancelled.']);
        }

        $indent->forceFill([
            'status' => PurchaseIndentStatus::Cancelled,
            'updated_by' => Auth::id(),
        ])->save();

        return $this->find($id);
    }
}
