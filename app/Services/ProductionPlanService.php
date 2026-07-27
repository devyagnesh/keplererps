<?php

namespace App\Services;

use App\Enums\DocumentSeriesType;
use App\Enums\DocumentStatus;
use App\Enums\SalesOrderStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Bom;
use App\Models\Item;
use App\Models\ProductionPlan;
use App\Models\ProductionPlanItem;
use App\Models\ProductionPlanShortage;
use App\Models\SalesOrderItem;
use App\Models\StockBalance;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Repositories\Interfaces\ProductionPlanRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Production planning: turn open sales demand into draft work orders (M09 planning half).
 */
class ProductionPlanService
{
    public function __construct(
        protected ProductionPlanRepositoryInterface $repository,
        protected WorkOrderService $workOrders,
        protected BomService $boms,
        protected NumberingService $numbering,
        protected ActivityLogService $activityLog
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): ProductionPlan
    {
        return $this->repository->findById($id);
    }

    /**
     * Open sales order demand that still needs work orders (US-M09-01).
     *
     * A line is demand when the sales order is confirmed or partially delivered, the item is
     * manufacturable with an active BOM, and the undelivered quantity is not fully planned.
     *
     * @return list<array<string, mixed>>
     */
    public function demandLines(?string $fromDate = null, ?string $toDate = null): array
    {
        $lines = SalesOrderItem::query()
            ->with([
                'item:id,item_code,item_name,is_manufacturable,is_active',
                'salesOrder:id,document_no,document_date,customer_id,expected_delivery_date,status',
                'salesOrder.customer:id,party_code,party_name',
            ])
            ->whereHas('item', fn ($q) => $q->where('is_manufacturable', true)->where('is_active', true))
            ->whereHas('salesOrder', function ($q) use ($fromDate, $toDate): void {
                $q->whereIn('status', [
                    SalesOrderStatus::Confirmed->value,
                    SalesOrderStatus::PartiallyDelivered->value,
                ]);

                if ($fromDate !== null) {
                    $q->where(function ($inner) use ($fromDate): void {
                        $inner->whereNull('expected_delivery_date')
                            ->orWhereDate('expected_delivery_date', '>=', $fromDate);
                    });
                }

                if ($toDate !== null) {
                    $q->where(function ($inner) use ($toDate): void {
                        $inner->whereNull('expected_delivery_date')
                            ->orWhereDate('expected_delivery_date', '<=', $toDate);
                    });
                }
            })
            ->get();

        $plannedBySoItem = $this->plannedQtyBySalesOrderItem($lines->pluck('id')->all());
        $rows = [];

        foreach ($lines as $line) {
            $bom = $this->activeBomForItem((int) $line->item_id);
            if ($bom === null) {
                continue;
            }

            $planned = (float) ($plannedBySoItem[$line->id] ?? 0);
            $open = round($line->pendingDeliveryQty() - $planned, 4);
            if ($open <= 0) {
                continue;
            }

            $rows[] = [
                'sales_order_id' => $line->sales_order_id,
                'sales_order_item_id' => $line->id,
                'sales_order_no' => $line->salesOrder?->document_no,
                'customer' => $line->salesOrder?->customer?->party_name,
                'item_id' => $line->item_id,
                'item_code' => $line->item?->item_code,
                'item_name' => $line->item?->item_name,
                'bom_id' => $bom->id,
                'bom_label' => $bom->bom_number.' v'.$bom->version,
                'ordered_quantity' => round((float) $line->quantity, 4),
                'planned_quantity' => $planned,
                'open_quantity' => $open,
                'required_date' => $line->salesOrder?->expected_delivery_date?->toDateString(),
            ];
        }

        return Collection::make($rows)
            ->sortBy(fn (array $row) => [$row['required_date'] ?? '9999-12-31', $row['sales_order_no']])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ProductionPlan
    {
        return DB::transaction(function () use ($data): ProductionPlan {
            $lines = $data['items'] ?? [];
            unset($data['items']);

            $this->assertWarehouses((int) $data['source_warehouse_id'], (int) $data['target_warehouse_id']);
            $this->assertHorizon($data);

            $data['document_no'] = $this->numbering->next(DocumentSeriesType::ProductionPlan);
            $data['status'] = DocumentStatus::Draft->value;
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            $plan = $this->repository->create($data);
            $this->syncItems($plan, $lines);
            $this->refreshShortages($plan->fresh());

            return $this->repository->findById($plan->id);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): ProductionPlan
    {
        return DB::transaction(function () use ($id, $data): ProductionPlan {
            $plan = $this->repository->findById($id);
            $this->assertDraft($plan);

            $lines = $data['items'] ?? [];
            unset($data['items'], $data['document_no'], $data['status']);

            $this->assertWarehouses(
                (int) ($data['source_warehouse_id'] ?? $plan->source_warehouse_id),
                (int) ($data['target_warehouse_id'] ?? $plan->target_warehouse_id)
            );
            $this->assertHorizon(array_merge([
                'plan_from_date' => $plan->plan_from_date->toDateString(),
                'plan_to_date' => $plan->plan_to_date->toDateString(),
            ], $data));

            $data['updated_by'] = Auth::id();
            $this->repository->update($id, $data);

            $plan->refresh();
            $this->syncItems($plan, $lines);
            $this->refreshShortages($plan->fresh());

            return $this->repository->findById($id);
        });
    }

    public function delete(int $id): bool
    {
        $plan = $this->repository->findById($id);
        $this->assertDraft($plan);

        return $this->repository->delete($id);
    }

    /**
     * Generate one draft work order per plan line and freeze the component shortages.
     */
    public function generateWorkOrders(int $id): ProductionPlan
    {
        return DB::transaction(function () use ($id): ProductionPlan {
            $plan = ProductionPlan::query()->with('items')->lockForUpdate()->findOrFail($id);
            $this->assertDraft($plan);

            if ($plan->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Add at least one plan line before generating work orders.',
                ]);
            }

            foreach ($plan->items as $line) {
                if ($line->work_order_id !== null) {
                    continue;
                }

                $workOrder = $this->workOrders->create([
                    'document_date' => $plan->document_date->toDateString(),
                    'item_id' => $line->item_id,
                    'bom_id' => $line->bom_id,
                    'planned_quantity' => (float) $line->planned_quantity,
                    'sales_order_id' => $line->sales_order_id,
                    'sales_order_item_id' => $line->sales_order_item_id,
                    'planned_start_date' => $plan->plan_from_date->toDateString(),
                    'planned_end_date' => $line->required_date?->toDateString() ?? $plan->plan_to_date->toDateString(),
                    'source_warehouse_id' => $plan->source_warehouse_id,
                    'target_warehouse_id' => $plan->target_warehouse_id,
                    'remarks' => 'Generated from production plan '.$plan->document_no,
                ]);

                $line->forceFill(['work_order_id' => $workOrder->id])->save();
            }

            $this->refreshShortages($plan->fresh());

            $plan->forceFill([
                'status' => DocumentStatus::Posted,
                'posted_at' => now(),
                'posted_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ])->save();

            $this->activityLog->log(
                event: 'status_changed',
                description: "Production plan {$plan->document_no} generated ".$plan->items->count().' draft work order(s).',
                subject: $plan,
                properties: ['new_status' => DocumentStatus::Posted->value],
                logName: 'production'
            );

            return $this->repository->findById($id);
        });
    }

    /**
     * Cancel the plan and delete any still-draft work orders it created.
     */
    public function cancel(int $id): ProductionPlan
    {
        return DB::transaction(function () use ($id): ProductionPlan {
            $plan = ProductionPlan::query()->with('items.workOrder')->lockForUpdate()->findOrFail($id);

            if ($plan->status === DocumentStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'production_plan' => 'This production plan is already cancelled.',
                ]);
            }

            foreach ($plan->items as $line) {
                $workOrder = $line->workOrder;
                if ($workOrder === null) {
                    continue;
                }

                if ($workOrder->status !== WorkOrderStatus::Draft) {
                    throw ValidationException::withMessages([
                        'production_plan' => "Work order {$workOrder->document_no} is already released; cancel it before cancelling the plan.",
                    ]);
                }

                $this->workOrders->delete($workOrder->id);
                $line->forceFill(['work_order_id' => null])->save();
            }

            $plan->forceFill([
                'status' => DocumentStatus::Cancelled,
                'updated_by' => Auth::id(),
            ])->save();

            return $this->repository->findById($id);
        });
    }

    /**
     * Component shortages of posted plans, shaped like reorder-based purchase suggestions.
     *
     * @return list<array<string, mixed>>
     */
    public function openShortages(?int $warehouseId = null): array
    {
        $shortages = ProductionPlanShortage::query()
            ->with([
                'item:id,item_code,item_name,is_purchasable,stock_uom_id,standard_cost',
                'productionPlan:id,document_no,status,source_warehouse_id',
                'productionPlan.sourceWarehouse:id,code,name',
            ])
            ->where('shortage_quantity', '>', 0)
            ->whereHas('productionPlan', function ($q) use ($warehouseId): void {
                $q->where('status', DocumentStatus::Posted->value);

                if ($warehouseId !== null) {
                    $q->where('source_warehouse_id', $warehouseId);
                }
            })
            ->get();

        return $shortages
            ->map(fn (ProductionPlanShortage $row): array => [
                'source' => 'production_plan',
                'reference' => $row->productionPlan?->document_no,
                'item_id' => $row->item_id,
                'item_code' => $row->item?->item_code,
                'item_name' => $row->item?->item_name,
                'warehouse_id' => $row->productionPlan?->source_warehouse_id,
                'warehouse' => $row->productionPlan?->sourceWarehouse?->name,
                'stock_uom_id' => $row->item?->stock_uom_id,
                'physical_qty' => round((float) $row->available_quantity, 4),
                'committed_qty' => 0.0,
                'free_qty' => round((float) $row->available_quantity, 4),
                'on_order_qty' => 0.0,
                'reorder_level' => round((float) $row->required_quantity, 4),
                'suggested_qty' => round((float) $row->shortage_quantity, 4),
                'rate' => (float) ($row->item?->standard_cost ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * Leaf warehouses selectable as plan source/target.
     *
     * @return Collection<int, Warehouse>
     */
    public function selectableWarehouses(): Collection
    {
        return Warehouse::query()
            ->where('is_leaf', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    protected function syncItems(ProductionPlan $plan, array $lines): void
    {
        $plan->items()->delete();

        foreach (array_values($lines) as $index => $line) {
            $quantity = round((float) ($line['planned_quantity'] ?? 0), 4);
            if (empty($line['item_id']) || $quantity <= 0) {
                continue;
            }

            $item = Item::query()->findOrFail((int) $line['item_id']);
            if (! $item->is_manufacturable) {
                throw ValidationException::withMessages([
                    'items' => "Item {$item->item_code} is not manufacturable.",
                ]);
            }

            $bom = ! empty($line['bom_id'])
                ? Bom::query()->findOrFail((int) $line['bom_id'])
                : $this->activeBomForItem((int) $item->id);

            if ($bom === null) {
                throw ValidationException::withMessages([
                    'items' => "No active BOM found for item {$item->item_code}.",
                ]);
            }

            if ((int) $bom->item_id !== (int) $item->id || ! $bom->is_active) {
                throw ValidationException::withMessages([
                    'items' => "Selected BOM is not an active BOM for item {$item->item_code}.",
                ]);
            }

            ProductionPlanItem::query()->create([
                'production_plan_id' => $plan->id,
                'item_id' => $item->id,
                'bom_id' => $bom->id,
                'sales_order_id' => $line['sales_order_id'] ?? null,
                'sales_order_item_id' => $line['sales_order_item_id'] ?? null,
                'planned_quantity' => $quantity,
                'required_date' => $line['required_date'] ?? null,
                'sort_order' => $index,
            ]);
        }

        if ($plan->items()->count() === 0) {
            throw ValidationException::withMessages([
                'items' => 'Add at least one production plan line.',
            ]);
        }
    }

    /**
     * Explode every plan line through its BOM and store the net component shortage.
     */
    protected function refreshShortages(ProductionPlan $plan): void
    {
        $required = [];

        foreach ($plan->items()->get() as $line) {
            foreach ($this->boms->explodeRequirements((int) $line->bom_id, (float) $line->planned_quantity) as $component) {
                $itemId = (int) $component['component_item_id'];
                $required[$itemId] = round(($required[$itemId] ?? 0) + (float) $component['required_quantity'], 4);
            }
        }

        $plan->shortages()->delete();

        foreach ($required as $itemId => $requiredQty) {
            $available = $this->freeQty($itemId, (int) $plan->source_warehouse_id);
            $shortage = max(0, round($requiredQty - $available, 4));

            ProductionPlanShortage::query()->create([
                'production_plan_id' => $plan->id,
                'item_id' => $itemId,
                'required_quantity' => $requiredQty,
                'available_quantity' => $available,
                'shortage_quantity' => $shortage,
            ]);
        }
    }

    /**
     * Planned (non-cancelled) work order quantity per sales order line.
     *
     * @param  list<int>  $salesOrderItemIds
     * @return array<int, float>
     */
    protected function plannedQtyBySalesOrderItem(array $salesOrderItemIds): array
    {
        if ($salesOrderItemIds === []) {
            return [];
        }

        return WorkOrder::query()
            ->whereIn('sales_order_item_id', $salesOrderItemIds)
            ->where('status', '!=', WorkOrderStatus::Cancelled->value)
            ->groupBy('sales_order_item_id')
            ->selectRaw('sales_order_item_id, COALESCE(SUM(planned_quantity),0) as planned')
            ->pluck('planned', 'sales_order_item_id')
            ->map(fn ($qty): float => round((float) $qty, 4))
            ->all();
    }

    protected function activeBomForItem(int $itemId): ?Bom
    {
        return Bom::query()
            ->where('item_id', $itemId)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();
    }

    protected function freeQty(int $itemId, int $warehouseId): float
    {
        $balance = StockBalance::query()
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->selectRaw('COALESCE(SUM(qty),0) as qty, COALESCE(SUM(committed_qty),0) as committed_qty')
            ->first();

        return max(0, round((float) ($balance->qty ?? 0) - (float) ($balance->committed_qty ?? 0), 4));
    }

    protected function assertWarehouses(int $sourceId, int $targetId): void
    {
        foreach ([$sourceId, $targetId] as $id) {
            $warehouse = Warehouse::query()->findOrFail($id);
            if (! $warehouse->is_leaf || ! $warehouse->is_active) {
                throw ValidationException::withMessages([
                    'source_warehouse_id' => 'Plan warehouses must be active leaf warehouses.',
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertHorizon(array $data): void
    {
        if (! empty($data['plan_from_date']) && ! empty($data['plan_to_date'])
            && $data['plan_to_date'] < $data['plan_from_date']) {
            throw ValidationException::withMessages([
                'plan_to_date' => 'Plan end date cannot be before the start date.',
            ]);
        }
    }

    protected function assertDraft(ProductionPlan $plan): void
    {
        if ($plan->status !== DocumentStatus::Draft) {
            throw ValidationException::withMessages([
                'production_plan' => 'Only draft production plans can be modified.',
            ]);
        }
    }
}
