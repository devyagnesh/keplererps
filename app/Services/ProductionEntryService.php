<?php

namespace App\Services;

use App\Enums\BomIssueMethod;
use App\Enums\DocumentSeriesType;
use App\Enums\RejectionDisposition;
use App\Enums\StockTransactionType;
use App\Enums\TrackingType;
use App\Enums\WorkOrderStatus;
use App\Models\Batch;
use App\Models\ProductionEntry;
use App\Models\ProductionEntryMaterial;
use App\Models\WorkOrder;
use App\Models\WorkOrderComponent;
use App\Repositories\Interfaces\ProductionEntryRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Production entry posting — backflush, FG receipt, scrap (M09 / BR-30–31).
 */
class ProductionEntryService
{
    public function __construct(
        protected ProductionEntryRepositoryInterface $repository,
        protected WorkOrderService $workOrders,
        protected StockLedgerService $ledger,
        protected NumberingService $numbering,
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

    public function find(int $id): ProductionEntry
    {
        return $this->repository->findById($id);
    }

    /**
     * Create and immediately post a production entry (shop-floor fast path).
     *
     * @param  array<string, mixed>  $data
     */
    public function createAndPost(array $data): ProductionEntry
    {
        return DB::transaction(function () use ($data): ProductionEntry {
            $entry = $this->createDraft($data);

            return $this->post($entry->id);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ProductionEntry
    {
        return DB::transaction(fn (): ProductionEntry => $this->createDraft($data));
    }

    public function delete(int $id): bool
    {
        $entry = $this->repository->findById($id);
        if ($entry->posted_at !== null) {
            throw ValidationException::withMessages(['entry' => 'Posted production entries cannot be deleted.']);
        }

        return $this->repository->delete($id);
    }

    public function post(int $id): ProductionEntry
    {
        return DB::transaction(function () use ($id): ProductionEntry {
            $entry = ProductionEntry::query()->lockForUpdate()->findOrFail($id);
            if ($entry->posted_at !== null) {
                throw ValidationException::withMessages(['entry' => 'Production entry is already posted.']);
            }

            $wo = WorkOrder::query()
                ->with(['components', 'operations', 'bom', 'item'])
                ->lockForUpdate()
                ->findOrFail($entry->work_order_id);

            if (! $wo->status->canReceiveProduction()) {
                throw ValidationException::withMessages(['work_order_id' => 'Work order is not open for production.']);
            }

            $this->workCentres->assertCanReceiveProduction($wo->work_centre_id);
            $this->assertInProcessQcGate($wo);

            $good = (float) $entry->good_quantity;
            $rejected = (float) $entry->rejected_quantity;
            $this->assertQuantities($wo, $good, $rejected);
            $this->assertRejection($entry, $rejected);

            $materialCost = $this->backflushAndIssue($entry, $wo, $good);
            $machineCost = 0.0;
            $labourCost = 0.0;
            $machineHours = (float) $entry->machine_hours;
            $labourHours = (float) $entry->labour_hours;

            if ($machineHours <= 0 && $good > 0) {
                $machineHours = $this->estimateMachineHours($wo, $good);
            }
            if ($labourHours <= 0 && $good > 0) {
                $labourHours = $machineHours;
            }

            foreach ($wo->operations as $operation) {
                $machineCost += $machineHours * (float) $operation->machine_rate_per_hour;
                $labourCost += $labourHours * (float) $operation->labour_rate_per_hour;
            }
            $machineCost = round($machineCost, 2);
            $labourCost = round($labourCost, 2);
            $overheadPct = (float) ($wo->bom?->overhead_percent ?? 0);
            $overhead = round(($materialCost + $machineCost + $labourCost) * ($overheadPct / 100), 2);
            $total = round($materialCost + $machineCost + $labourCost + $overhead, 2);
            $unitRate = $good > 0 ? round($total / $good, 4) : 0.0;

            if ($good > 0) {
                $batchId = $this->resolveOutputBatch($entry, $wo);
                $this->ledger->post([
                    'item_id' => $wo->item_id,
                    'warehouse_id' => $wo->target_warehouse_id,
                    'batch_id' => $batchId,
                    'transaction_type' => StockTransactionType::ProductionReceipt,
                    'posting_at' => $entry->document_date->copy()->startOfDay(),
                    'qty_in' => $good,
                    'qty_out' => 0,
                    'rate' => $unitRate,
                    'source' => $entry,
                    'remarks' => $entry->document_no,
                ]);
                if ($batchId) {
                    $entry->forceFill(['batch_id' => $batchId])->save();
                }
            }

            if ($rejected > 0 && $entry->rejection_disposition === RejectionDisposition::Scrap) {
                $this->ledger->post([
                    'item_id' => $wo->item_id,
                    'warehouse_id' => $wo->target_warehouse_id,
                    'transaction_type' => StockTransactionType::ScrapReceipt,
                    'posting_at' => $entry->document_date->copy()->startOfDay(),
                    'qty_in' => $rejected,
                    'qty_out' => 0,
                    'rate' => 0,
                    'source' => $entry,
                    'remarks' => $entry->document_no.' scrap',
                ]);
            }

            if ($rejected > 0 && $entry->rejection_disposition === RejectionDisposition::Downgrade) {
                if (! $entry->downgrade_item_id) {
                    throw ValidationException::withMessages(['downgrade_item_id' => 'Downgrade item is required.']);
                }
                $this->ledger->post([
                    'item_id' => $entry->downgrade_item_id,
                    'warehouse_id' => $wo->target_warehouse_id,
                    'transaction_type' => StockTransactionType::ProductionReceipt,
                    'posting_at' => $entry->document_date->copy()->startOfDay(),
                    'qty_in' => $rejected,
                    'qty_out' => 0,
                    'rate' => 0,
                    'source' => $entry,
                    'remarks' => $entry->document_no.' downgrade',
                ]);
            }

            $entry->forceFill([
                'machine_hours' => $machineHours,
                'labour_hours' => $labourHours,
                'material_cost' => $materialCost,
                'machine_cost' => $machineCost,
                'labour_cost' => $labourCost,
                'overhead_cost' => $overhead,
                'total_cost' => $total,
                'posted_at' => now(),
                'posted_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ])->save();

            $wo->forceFill([
                'good_quantity' => round((float) $wo->good_quantity + $good, 4),
                'rejected_quantity' => round((float) $wo->rejected_quantity + $rejected, 4),
                'status' => $this->nextStatusAfterEntry($wo, $good),
                'updated_by' => Auth::id(),
            ])->save();

            $this->workCentres->recordProductionUsage($wo->work_centre_id, $good, $machineHours);

            $posted = $this->repository->findById($id);
            app(QcInspectionService::class)->createFromProductionEntry($posted);

            return $posted;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function createDraft(array $data): ProductionEntry
    {
        $wo = WorkOrder::query()->findOrFail((int) $data['work_order_id']);
        if (! $wo->status->canReceiveProduction()) {
            throw ValidationException::withMessages(['work_order_id' => 'Work order is not open for production.']);
        }

        $this->workCentres->assertCanReceiveProduction($wo->work_centre_id);

        $good = round((float) ($data['good_quantity'] ?? 0), 4);
        $rejected = round((float) ($data['rejected_quantity'] ?? 0), 4);
        if ($good <= 0 && $rejected <= 0) {
            throw ValidationException::withMessages(['good_quantity' => 'Enter good and/or rejected quantity.']);
        }

        return $this->repository->create([
            'document_no' => $this->numbering->next(DocumentSeriesType::ProductionEntry),
            'document_date' => $data['document_date'] ?? now()->toDateString(),
            'work_order_id' => $wo->id,
            'good_quantity' => $good,
            'rejected_quantity' => $rejected,
            'defect_reason_id' => $data['defect_reason_id'] ?? null,
            'rejection_disposition' => $data['rejection_disposition'] ?? null,
            'downgrade_item_id' => $data['downgrade_item_id'] ?? null,
            'batch_no' => $data['batch_no'] ?? null,
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
            'downtime_minutes' => (int) ($data['downtime_minutes'] ?? 0),
            'downtime_reason' => $data['downtime_reason'] ?? null,
            'machine_hours' => (float) ($data['machine_hours'] ?? 0),
            'labour_hours' => (float) ($data['labour_hours'] ?? 0),
            'operator_user_id' => $data['operator_user_id'] ?? Auth::id(),
            'remarks' => $data['remarks'] ?? null,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }

    protected function backflushAndIssue(ProductionEntry $entry, WorkOrder $wo, float $goodQty): float
    {
        $materialCost = 0.0;
        if ($goodQty <= 0) {
            return 0.0;
        }

        $planned = (float) $wo->planned_quantity;
        foreach ($wo->components as $component) {
            if ($component->issue_method !== BomIssueMethod::Backflush) {
                continue;
            }

            $qty = round(((float) $component->required_quantity / $planned) * $goodQty, 4);
            if ($qty <= 0) {
                continue;
            }

            $ledger = $this->ledger->post([
                'item_id' => $component->item_id,
                'warehouse_id' => $wo->source_warehouse_id,
                'transaction_type' => StockTransactionType::MaterialIssue,
                'posting_at' => $entry->document_date->copy()->startOfDay(),
                'qty_in' => 0,
                'qty_out' => $qty,
                'source' => $entry,
                'remarks' => $entry->document_no.' backflush',
            ]);

            $this->releaseCommitted((int) $component->item_id, (int) $wo->source_warehouse_id, $qty);

            $component->forceFill([
                'issued_quantity' => round((float) $component->issued_quantity + $qty, 4),
            ])->save();

            $value = (float) $ledger->value;
            $materialCost += $value;

            ProductionEntryMaterial::query()->create([
                'production_entry_id' => $entry->id,
                'work_order_component_id' => $component->id,
                'item_id' => $component->item_id,
                'uom_id' => $component->uom_id,
                'quantity' => $qty,
                'rate' => (float) $ledger->rate,
                'value' => $value,
                'issue_method' => BomIssueMethod::Backflush->value,
            ]);
        }

        return round($materialCost, 2);
    }

    protected function resolveOutputBatch(ProductionEntry $entry, WorkOrder $wo): ?int
    {
        if ($wo->item?->tracking_type !== TrackingType::Batch) {
            return null;
        }

        $batchNo = $entry->batch_no ?: ($wo->document_no.'-'.now()->format('YmdHis'));
        $batch = Batch::query()->firstOrCreate(
            [
                'item_id' => $wo->item_id,
                'batch_no' => $batchNo,
            ],
            [
                'mfg_date' => $entry->document_date,
                'is_active' => true,
            ]
        );

        return $batch->id;
    }

    protected function estimateMachineHours(WorkOrder $wo, float $goodQty): float
    {
        $minutes = 0.0;
        foreach ($wo->operations as $operation) {
            $minutes += (float) $operation->setup_time_minutes;
            $minutes += (float) $operation->run_time_per_unit_minutes * $goodQty;
        }

        return round($minutes / 60, 4);
    }

    protected function nextStatusAfterEntry(WorkOrder $wo, float $goodAdded): string
    {
        $wo->refresh();
        $tolerance = WorkOrderService::OVER_PRODUCTION_TOLERANCE_PERCENT / 100;
        $max = (float) $wo->planned_quantity * (1 + $tolerance);
        if ((float) $wo->good_quantity + 0.00005 >= (float) $wo->planned_quantity) {
            return WorkOrderStatus::Completed->value;
        }

        return WorkOrderStatus::InProgress->value;
    }

    protected function assertQuantities(WorkOrder $wo, float $good, float $rejected): void
    {
        $tolerance = WorkOrderService::OVER_PRODUCTION_TOLERANCE_PERCENT / 100;
        $max = (float) $wo->planned_quantity * (1 + $tolerance);
        if ((float) $wo->good_quantity + $good - $max > 0.0001) {
            throw ValidationException::withMessages([
                'good_quantity' => 'Good quantity exceeds planned quantity plus over-production tolerance.',
            ]);
        }
    }

    protected function assertRejection(ProductionEntry $entry, float $rejected): void
    {
        if ($rejected <= 0) {
            return;
        }
        if (! $entry->defect_reason_id) {
            throw ValidationException::withMessages(['defect_reason_id' => 'Defect reason is required for rejections.']);
        }
        if (! $entry->rejection_disposition) {
            throw ValidationException::withMessages(['rejection_disposition' => 'Rejection disposition is required.']);
        }
    }

    /**
     * Block posting when the work order has QC-gated operations without a passing inspection.
     */
    protected function assertInProcessQcGate(WorkOrder $wo): void
    {
        $requiresQc = $wo->operations->contains(fn ($op): bool => (bool) $op->requires_qc);
        if (! $requiresQc) {
            return;
        }

        $passed = \App\Models\QcInspection::query()
            ->where('status', \App\Enums\InspectionStatus::Completed->value)
            ->where('overall_result', 'pass')
            ->where(function ($q) use ($wo): void {
                $q->where(function ($inner) use ($wo): void {
                    $inner->where('source_type', WorkOrder::class)
                        ->where('source_id', $wo->id);
                })->orWhere(function ($inner) use ($wo): void {
                    $inner->where('source_type', ProductionEntry::class)
                        ->whereIn('source_id', ProductionEntry::query()->where('work_order_id', $wo->id)->select('id'));
                });
            })
            ->exists();

        if (! $passed) {
            throw ValidationException::withMessages([
                'work_order_id' => 'In-process QC is required and must pass before posting production on this work order.',
            ]);
        }
    }

    protected function releaseCommitted(int $itemId, int $warehouseId, float $qty): void
    {
        $balance = \App\Models\StockBalance::query()
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->where('batch_key', 0)
            ->first();

        if ($balance === null) {
            return;
        }

        $balance->forceFill([
            'committed_qty' => max(0, round((float) $balance->committed_qty - $qty, 4)),
        ])->save();
    }
}
