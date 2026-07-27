<?php

namespace App\Services;

use App\Enums\BomIssueMethod;
use App\Enums\DocumentSeriesType;
use App\Enums\SalesOrderStatus;
use App\Enums\StockTransactionType;
use App\Enums\TrackingType;
use App\Enums\WorkOrderPriority;
use App\Enums\WorkOrderStatus;
use App\Models\Bom;
use App\Models\Item;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockBalance;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Models\WorkOrderComponent;
use App\Models\WorkOrderOperation;
use App\Repositories\Interfaces\WorkOrderRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Work order planning, release, material issue and close (M09).
 */
class WorkOrderService
{
    public const OVER_PRODUCTION_TOLERANCE_PERCENT = 5.0;

    public function __construct(
        protected WorkOrderRepositoryInterface $repository,
        protected BomService $boms,
        protected StockLedgerService $ledger,
        protected NumberingService $numbering,
        protected ActivityLogService $activityLog,
        protected WorkCentreService $workCentres
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): WorkOrder
    {
        return $this->repository->findById($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): WorkOrder
    {
        return DB::transaction(function () use ($data): WorkOrder {
            $item = Item::query()->findOrFail((int) $data['item_id']);
            if (! $item->is_manufacturable) {
                throw ValidationException::withMessages(['item_id' => 'Item must be manufacturable.']);
            }

            $bom = Bom::query()->with(['components', 'operations'])->findOrFail((int) $data['bom_id']);
            if ((int) $bom->item_id !== (int) $item->id) {
                throw ValidationException::withMessages(['bom_id' => 'BOM does not belong to the selected item.']);
            }
            if (! $bom->is_active) {
                throw ValidationException::withMessages(['bom_id' => 'BOM must be active.']);
            }

            $this->assertWarehouses((int) $data['source_warehouse_id'], (int) $data['target_warehouse_id']);
            $this->assertDates($data);
            $this->assertSalesOrderLink($data, (float) $data['planned_quantity'], $item->id);
            $this->workCentres->assertCanReceiveProduction(
                isset($data['work_centre_id']) ? (int) $data['work_centre_id'] : null
            );

            $outputQty = (float) $bom->output_quantity;
            $planned = (float) $data['planned_quantity'];
            $standardUnit = $outputQty > 0
                ? round((float) $bom->rolled_total_cost / $outputQty, 4)
                : 0.0;

            $header = [
                'document_no' => $this->numbering->next(DocumentSeriesType::WorkOrder),
                'document_date' => $data['document_date'],
                'item_id' => $item->id,
                'bom_id' => $bom->id,
                'planned_quantity' => $planned,
                'good_quantity' => 0,
                'rejected_quantity' => 0,
                'sales_order_id' => $data['sales_order_id'] ?? null,
                'sales_order_item_id' => $data['sales_order_item_id'] ?? null,
                'planned_start_date' => $data['planned_start_date'],
                'planned_end_date' => $data['planned_end_date'],
                'source_warehouse_id' => $data['source_warehouse_id'],
                'target_warehouse_id' => $data['target_warehouse_id'],
                'work_centre_id' => $data['work_centre_id'] ?? null,
                'priority' => $data['priority'] ?? WorkOrderPriority::Normal->value,
                'status' => WorkOrderStatus::Draft->value,
                'bom_version_reason' => $data['bom_version_reason'] ?? null,
                'standard_unit_cost' => $standardUnit,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ];

            $wo = $this->repository->create($header);
            $this->syncBomSnapshot($wo, $bom, $planned);

            return $this->repository->findById($wo->id);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): WorkOrder
    {
        return DB::transaction(function () use ($id, $data): WorkOrder {
            $wo = $this->repository->findById($id);
            if (! $wo->status->isEditable()) {
                throw ValidationException::withMessages(['work_order' => 'Only draft work orders can be edited.']);
            }

            unset($data['document_no'], $data['status'], $data['item_id'], $data['bom_id']);
            $this->assertWarehouses(
                (int) ($data['source_warehouse_id'] ?? $wo->source_warehouse_id),
                (int) ($data['target_warehouse_id'] ?? $wo->target_warehouse_id)
            );
            $this->workCentres->assertCanReceiveProduction(
                (int) ($data['work_centre_id'] ?? $wo->work_centre_id)
            );
            $this->assertDates(array_merge([
                'planned_start_date' => $wo->planned_start_date->toDateString(),
                'planned_end_date' => $wo->planned_end_date->toDateString(),
            ], $data));

            $planned = (float) ($data['planned_quantity'] ?? $wo->planned_quantity);
            $this->assertSalesOrderLink(array_merge([
                'sales_order_id' => $wo->sales_order_id,
                'sales_order_item_id' => $wo->sales_order_item_id,
            ], $data), $planned, (int) $wo->item_id, $wo->id);

            $data['updated_by'] = Auth::id();
            $data['planned_quantity'] = $planned;
            $this->repository->update($id, $data);

            $bom = Bom::query()->with(['components', 'operations'])->findOrFail($wo->bom_id);
            $wo->components()->delete();
            $wo->operations()->delete();
            $this->syncBomSnapshot($wo->fresh(), $bom, $planned);

            return $this->repository->findById($id);
        });
    }

    public function delete(int $id): bool
    {
        $wo = $this->repository->findById($id);
        if (! $wo->status->isEditable()) {
            throw ValidationException::withMessages(['work_order' => 'Only draft work orders can be deleted.']);
        }

        return $this->repository->delete($id);
    }

    /**
     * Material availability for release screen (US-M09-02).
     *
     * @return list<array<string, mixed>>
     */
    public function materialAvailability(int $id): array
    {
        $wo = $this->repository->findById($id);
        $rows = [];
        foreach ($wo->components as $component) {
            $available = $this->availableQty((int) $component->item_id, (int) $wo->source_warehouse_id);
            $required = (float) $component->required_quantity;
            $shortage = max(0, round($required - $available, 4));
            $rows[] = [
                'item_id' => $component->item_id,
                'item_code' => $component->item?->item_code,
                'item_name' => $component->item?->item_name,
                'required_quantity' => $required,
                'available_quantity' => $available,
                'shortage_quantity' => $shortage,
                'is_critical' => $component->is_critical,
                'issue_method' => $component->issue_method->value,
            ];
        }

        return $rows;
    }

    /**
     * Release WO: BR-29 critical shortage blocks; non-critical needs confirm.
     *
     * @param  array{confirm_non_critical?: bool}  $options
     */
    public function release(int $id, array $options = []): WorkOrder
    {
        return DB::transaction(function () use ($id, $options): WorkOrder {
            $wo = WorkOrder::query()->with('components')->lockForUpdate()->findOrFail($id);
            if (! $wo->status->canRelease()) {
                throw ValidationException::withMessages(['work_order' => 'Only draft work orders can be released.']);
            }

            $this->workCentres->assertCanReceiveProduction($wo->work_centre_id);

            if ($wo->components->isEmpty()) {
                throw ValidationException::withMessages(['components' => 'Work order has no BOM components.']);
            }

            $availability = $this->materialAvailability($id);
            $criticalShort = collect($availability)->first(fn (array $r) => $r['is_critical'] && $r['shortage_quantity'] > 0);
            if ($criticalShort !== null) {
                throw ValidationException::withMessages([
                    'materials' => "Critical component {$criticalShort['item_code']} is short by {$criticalShort['shortage_quantity']}.",
                ]);
            }

            $nonCriticalShort = collect($availability)->contains(fn (array $r) => ! $r['is_critical'] && $r['shortage_quantity'] > 0);
            if ($nonCriticalShort && empty($options['confirm_non_critical'])) {
                throw ValidationException::withMessages([
                    'confirm_non_critical' => 'Non-critical material shortages exist. Confirm to release anyway.',
                    'availability' => $availability,
                ]);
            }

            foreach ($wo->components as $component) {
                $this->adjustCommitted(
                    (int) $component->item_id,
                    (int) $wo->source_warehouse_id,
                    (float) $component->required_quantity,
                    true
                );
            }

            $wo->forceFill([
                'status' => WorkOrderStatus::Released,
                'released_at' => now(),
                'released_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ])->save();

            $this->activityLog->log(
                event: 'status_changed',
                description: "Work order {$wo->document_no} released.",
                subject: $wo,
                properties: ['new_status' => WorkOrderStatus::Released->value],
                logName: 'production'
            );

            return $this->repository->findById($id);
        });
    }

    /**
     * Manual material issue for components with issue_method = manual (BR-30 path).
     *
     * @param  list<array{work_order_component_id: int, quantity: float}>  $lines
     */
    public function issueMaterials(int $id, array $lines): WorkOrder
    {
        return DB::transaction(function () use ($id, $lines): WorkOrder {
            $wo = WorkOrder::query()->with('components')->lockForUpdate()->findOrFail($id);
            if (! in_array($wo->status, [WorkOrderStatus::Released, WorkOrderStatus::InProgress], true)) {
                throw ValidationException::withMessages(['work_order' => 'Issue materials only on released or in-progress work orders.']);
            }

            $components = $wo->components->keyBy('id');
            foreach ($lines as $line) {
                $component = $components->get((int) $line['work_order_component_id']);
                if ($component === null) {
                    throw ValidationException::withMessages(['items' => 'Invalid work order component.']);
                }
                if ($component->issue_method !== BomIssueMethod::Manual) {
                    throw ValidationException::withMessages(['items' => 'Only manual-issue components can be issued here.']);
                }

                $qty = round((float) $line['quantity'], 4);
                if ($qty <= 0 || $qty - $component->pendingIssueQty() > 0.0001) {
                    throw ValidationException::withMessages(['items' => 'Issue quantity exceeds pending requirement.']);
                }

                foreach ($this->issueAllocations($component, (int) $wo->source_warehouse_id, $qty) as $allocation) {
                    $this->ledger->post([
                        'item_id' => $component->item_id,
                        'warehouse_id' => $wo->source_warehouse_id,
                        'batch_id' => $allocation['batch_id'],
                        'transaction_type' => StockTransactionType::MaterialIssue,
                        'posting_at' => now(),
                        'qty_in' => 0,
                        'qty_out' => $allocation['quantity'],
                        'source' => $wo,
                        'remarks' => $wo->document_no.' material issue',
                    ]);
                }

                $this->adjustCommitted((int) $component->item_id, (int) $wo->source_warehouse_id, $qty, false);

                $component->forceFill([
                    'issued_quantity' => round((float) $component->issued_quantity + $qty, 4),
                ])->save();
            }

            if ($wo->status === WorkOrderStatus::Released) {
                $wo->forceFill(['status' => WorkOrderStatus::InProgress, 'updated_by' => Auth::id()])->save();
            }

            return $this->repository->findById($id);
        });
    }

    public function close(int $id): WorkOrder
    {
        return DB::transaction(function () use ($id): WorkOrder {
            $wo = WorkOrder::query()
                ->with(['productionEntries', 'components', 'bom'])
                ->lockForUpdate()
                ->findOrFail($id);

            if ($wo->status === WorkOrderStatus::Closed) {
                throw ValidationException::withMessages(['work_order' => 'Work order is already closed.']);
            }
            if ((float) $wo->good_quantity <= 0 && (float) $wo->rejected_quantity <= 0) {
                throw ValidationException::withMessages(['work_order' => 'Record production before closing.']);
            }

            $material = round((float) $wo->productionEntries->sum('material_cost'), 2);
            $machine = round((float) $wo->productionEntries->sum('machine_cost'), 2);
            $labour = round((float) $wo->productionEntries->sum('labour_cost'), 2);
            $overheadPct = (float) ($wo->bom?->overhead_percent ?? 0);
            $subtotal = $material + $machine + $labour;
            $overhead = round($subtotal * ($overheadPct / 100), 2);
            $total = round($subtotal + $overhead, 2);
            $good = (float) $wo->good_quantity;
            $unit = $good > 0 ? round($total / $good, 4) : 0.0;
            $standardTotal = round((float) $wo->standard_unit_cost * $good, 2);

            // Release any remaining commitment on unissued components.
            foreach ($wo->components as $component) {
                $remaining = $component->pendingIssueQty();
                if ($remaining > 0) {
                    $this->adjustCommitted((int) $component->item_id, (int) $wo->source_warehouse_id, $remaining, false);
                }
            }

            $wo->forceFill([
                'status' => WorkOrderStatus::Closed,
                'actual_material_cost' => $material,
                'actual_machine_cost' => $machine,
                'actual_labour_cost' => $labour,
                'actual_overhead_cost' => $overhead,
                'actual_total_cost' => $total,
                'actual_unit_cost' => $unit,
                'cost_variance' => round($total - $standardTotal, 2),
                'closed_at' => now(),
                'closed_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ])->save();

            $this->activityLog->log(
                event: 'status_changed',
                description: "Work order {$wo->document_no} closed. Variance {$wo->cost_variance}.",
                subject: $wo,
                properties: [
                    'actual_total_cost' => $total,
                    'standard_total_cost' => $standardTotal,
                    'cost_variance' => (float) $wo->cost_variance,
                ],
                logName: 'production'
            );

            return $this->repository->findById($id);
        });
    }

    /**
     * Active BOMs for an item (create form helper).
     *
     * @return list<array<string, mixed>>
     */
    public function activeBomsForItem(int $itemId): array
    {
        return Bom::query()
            ->where('item_id', $itemId)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->get(['id', 'bom_number', 'version', 'output_quantity', 'rolled_total_cost'])
            ->map(fn (Bom $bom): array => [
                'id' => $bom->id,
                'label' => $bom->bom_number.' v'.$bom->version,
                'version' => $bom->version,
                'output_quantity' => (float) $bom->output_quantity,
                'rolled_total_cost' => (float) $bom->rolled_total_cost,
            ])
            ->all();
    }

    protected function syncBomSnapshot(WorkOrder $wo, Bom $bom, float $plannedQty): void
    {
        $outputQty = (float) $bom->output_quantity;
        foreach ($bom->components->values() as $index => $component) {
            $wastage = (float) $component->wastage_percent;
            $required = ($component->quantity / $outputQty) * $plannedQty * (1 + ($wastage / 100));
            WorkOrderComponent::query()->create([
                'work_order_id' => $wo->id,
                'item_id' => $component->component_item_id,
                'uom_id' => $component->uom_id,
                'required_quantity' => round($required, 4),
                'issued_quantity' => 0,
                'is_critical' => $component->is_critical,
                'issue_method' => $component->issue_method->value,
                'sort_order' => $index,
            ]);
        }

        foreach ($bom->operations as $operation) {
            WorkOrderOperation::query()->create([
                'work_order_id' => $wo->id,
                'sequence' => $operation->sequence,
                'manufacturing_operation_id' => $operation->manufacturing_operation_id,
                'work_centre_id' => $operation->work_centre_id,
                'setup_time_minutes' => $operation->setup_time_minutes,
                'run_time_per_unit_minutes' => $operation->run_time_per_unit_minutes,
                'machine_rate_per_hour' => $operation->machine_rate_per_hour,
                'labour_rate_per_hour' => $operation->labour_rate_per_hour,
            ]);
        }
    }

    /**
     * Resolve the batch split for a material issue.
     *
     * Batch-tracked components are allocated FEFO from the source warehouse; everything
     * else is issued as a single untracked movement.
     *
     * @return list<array{batch_id: int|null, quantity: float}>
     */
    protected function issueAllocations(WorkOrderComponent $component, int $warehouseId, float $qty): array
    {
        $item = Item::query()->findOrFail($component->item_id);

        if ($item->tracking_type !== TrackingType::Batch) {
            return [['batch_id' => null, 'quantity' => $qty]];
        }

        return $this->ledger->allocateFefo((int) $item->id, $warehouseId, $qty);
    }

    protected function availableQty(int $itemId, int $warehouseId): float
    {
        $balance = StockBalance::query()
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->where('batch_key', 0)
            ->first();

        if ($balance === null) {
            return 0.0;
        }

        return max(0, round((float) $balance->qty - (float) $balance->committed_qty, 4));
    }

    protected function adjustCommitted(int $itemId, int $warehouseId, float $qty, bool $increase): void
    {
        $balance = StockBalance::query()->firstOrCreate(
            [
                'item_id' => $itemId,
                'warehouse_id' => $warehouseId,
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

        $delta = $qty * ($increase ? 1 : -1);
        $balance->forceFill([
            'committed_qty' => max(0, round((float) $balance->committed_qty + $delta, 4)),
        ])->save();
    }

    protected function assertWarehouses(int $sourceId, int $targetId): void
    {
        foreach ([$sourceId, $targetId] as $id) {
            $wh = Warehouse::query()->findOrFail($id);
            if (! $wh->is_leaf || ! $wh->is_active) {
                throw ValidationException::withMessages(['warehouse_id' => 'Warehouses must be active leaf warehouses.']);
            }
        }
    }

    /** @param  array<string, mixed>  $data */
    protected function assertDates(array $data): void
    {
        if (! empty($data['planned_start_date']) && ! empty($data['planned_end_date'])
            && $data['planned_end_date'] < $data['planned_start_date']) {
            throw ValidationException::withMessages([
                'planned_end_date' => 'Planned end cannot be before planned start.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertSalesOrderLink(array $data, float $plannedQty, int $itemId, ?int $ignoreWoId = null): void
    {
        if (empty($data['sales_order_id'])) {
            return;
        }

        $order = SalesOrder::query()->findOrFail((int) $data['sales_order_id']);
        if (! in_array($order->status, [
            SalesOrderStatus::Confirmed,
            SalesOrderStatus::PartiallyDelivered,
            SalesOrderStatus::Delivered,
        ], true)) {
            throw ValidationException::withMessages(['sales_order_id' => 'Sales order must be confirmed.']);
        }

        $soItemId = $data['sales_order_item_id'] ?? null;
        $soItem = $soItemId
            ? SalesOrderItem::query()->findOrFail((int) $soItemId)
            : SalesOrderItem::query()->where('sales_order_id', $order->id)->where('item_id', $itemId)->first();

        if ($soItem === null || (int) $soItem->item_id !== $itemId) {
            throw ValidationException::withMessages(['sales_order_item_id' => 'Sales order does not contain this item.']);
        }

        $alreadyPlanned = (float) WorkOrder::query()
            ->where('sales_order_item_id', $soItem->id)
            ->when($ignoreWoId, fn ($q) => $q->where('id', '!=', $ignoreWoId))
            ->whereNotIn('status', [WorkOrderStatus::Cancelled->value])
            ->sum('planned_quantity');

        if ($alreadyPlanned + $plannedQty - (float) $soItem->quantity > 0.0001) {
            throw ValidationException::withMessages([
                'planned_quantity' => 'Linked work order quantity exceeds sales order line quantity.',
            ]);
        }
    }
}
