<?php

namespace App\Services;

use App\Enums\DocumentSeriesType;
use App\Enums\InspectionStatus;
use App\Enums\InspectionType;
use App\Enums\QcDisposition;
use App\Enums\QcParameterType;
use App\Enums\StockTransactionType;
use App\Models\GoodsReceipt;
use App\Models\Item;
use App\Models\ProductionEntry;
use App\Models\QcInspection;
use App\Models\QcInspectionReading;
use App\Models\SalesOrder;
use App\Models\WorkOrder;
use App\Repositories\Interfaces\QcInspectionRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * QC inspection create / complete with stock disposition (M10).
 */
class QcInspectionService
{
    public function __construct(
        protected QcInspectionRepositoryInterface $repository,
        protected QcTemplateService $templates,
        protected StockLedgerService $ledger,
        protected NumberingService $numbering,
        protected WarehouseResolver $warehouses,
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

    public function find(int $id): QcInspection
    {
        return $this->repository->findById($id);
    }

    /**
     * US-M10-01 — create pending incoming inspections from a posted GRN.
     *
     * @return list<QcInspection>
     */
    public function createFromGoodsReceipt(GoodsReceipt $grn): array
    {
        $created = [];
        $quarantine = $this->warehouses->quarantineWarehouse($grn->warehouse?->branch_id);

        foreach ($grn->items as $line) {
            $requires = (bool) ($line->purchaseOrderItem?->requires_inspection
                || $line->item?->requires_inspection);

            if (! $requires || (float) $line->accepted_qty <= 0) {
                continue;
            }

            $inspection = $this->createPending([
                'inspection_type' => InspectionType::Incoming,
                'source' => $grn,
                'item_id' => $line->item_id,
                'batch_id' => $line->batch_id,
                'lot_quantity' => (float) $line->accepted_qty,
                'quarantine_warehouse_id' => $quarantine->id,
                'target_warehouse_id' => $grn->warehouse_id,
                'document_date' => $grn->document_date->toDateString(),
            ]);

            $created[] = $inspection;
        }

        return $created;
    }

    /**
     * US-M10-02 — raise an in-process / final / pre-dispatch / customer-return inspection
     * manually. Incoming inspections are always generated from a posted goods receipt.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function createManual(array $data): QcInspection
    {
        $type = InspectionType::from((string) $data['inspection_type']);

        if ($type === InspectionType::Incoming) {
            throw ValidationException::withMessages([
                'inspection_type' => 'Incoming inspections are raised automatically when a goods receipt is posted.',
            ]);
        }

        $item = Item::query()->findOrFail((int) $data['item_id']);

        return $this->createPending([
            'inspection_type' => $type,
            'source' => $this->resolveManualSource($data, $item),
            'item_id' => $item->id,
            'batch_id' => $data['batch_id'] ?? null,
            'lot_quantity' => (float) $data['lot_quantity'],
            'quarantine_warehouse_id' => $data['quarantine_warehouse_id'] ?? null,
            'target_warehouse_id' => $data['target_warehouse_id'] ?? null,
            'document_date' => $data['document_date'] ?? now()->toDateString(),
            'sample_size' => $data['sample_size'] ?? null,
            'sample_override_reason' => $data['sample_override_reason'] ?? null,
            'remarks' => $data['remarks'] ?? null,
        ]);
    }

    /**
     * US-M10-02 — raise a pending final inspection for a posted production entry when an
     * active final-stage template exists for the produced item.
     */
    public function createFromProductionEntry(ProductionEntry $entry): ?QcInspection
    {
        $item = $entry->workOrder?->item;
        $good = round((float) $entry->good_quantity, 4);

        if ($item === null || $good <= 0) {
            return null;
        }

        $template = $this->templates->resolveForItem((int) $item->id, InspectionType::Final, $item->category_id);
        if ($template === null) {
            return null;
        }

        return $this->createPending([
            'inspection_type' => InspectionType::Final,
            'source' => $entry,
            'item_id' => (int) $item->id,
            'batch_id' => $entry->batch_id,
            'lot_quantity' => $good,
            // Finished goods stay in the production target warehouse; the inspection is a
            // record of conformance rather than a quarantine hold.
            'target_warehouse_id' => $entry->workOrder?->target_warehouse_id,
            'document_date' => $entry->document_date->toDateString(),
        ]);
    }

    /**
     * Resolve the polymorphic source for a manually raised inspection.
     *
     * @param  array<string, mixed>  $data
     */
    protected function resolveManualSource(array $data, Item $item): Model
    {
        if (! empty($data['work_order_id'])) {
            return WorkOrder::query()->findOrFail((int) $data['work_order_id']);
        }

        if (! empty($data['sales_order_id'])) {
            return SalesOrder::query()->findOrFail((int) $data['sales_order_id']);
        }

        // Standalone inspections anchor to the item so the source columns stay populated.
        return $item;
    }

    /**
     * @param  array{
     *     inspection_type: InspectionType|string,
     *     source: Model,
     *     item_id: int,
     *     batch_id?: int|null,
     *     lot_quantity: float,
     *     quarantine_warehouse_id?: int|null,
     *     target_warehouse_id?: int|null,
     *     document_date?: string,
     *     sample_size?: float|null,
     *     sample_override_reason?: string|null
     * }  $data
     */
    public function createPending(array $data): QcInspection
    {
        return DB::transaction(function () use ($data): QcInspection {
            $type = $data['inspection_type'] instanceof InspectionType
                ? $data['inspection_type']
                : InspectionType::from((string) $data['inspection_type']);

            /** @var Model $source */
            $source = $data['source'];
            $item = Item::query()->findOrFail((int) $data['item_id']);
            $template = $this->templates->resolveForItem($item->id, $type, $item->category_id);
            $lotQty = (float) $data['lot_quantity'];

            $sampleSize = isset($data['sample_size'])
                ? (float) $data['sample_size']
                : ($template ? $this->templates->suggestSampleSize($template, $lotQty) : min(1, $lotQty));

            if ($sampleSize <= 0 || $sampleSize - $lotQty > 0.0001) {
                throw ValidationException::withMessages(['sample_size' => 'Sample size must be > 0 and ≤ lot quantity.']);
            }

            if (isset($data['sample_size']) && $template
                && abs($sampleSize - $this->templates->suggestSampleSize($template, $lotQty)) > 0.0001
                && empty($data['sample_override_reason'])) {
                throw ValidationException::withMessages([
                    'sample_override_reason' => 'Provide a reason when overriding the suggested sample size.',
                ]);
            }

            $inspection = $this->repository->create([
                'document_no' => $this->numbering->next(DocumentSeriesType::QcInspection),
                'document_date' => $data['document_date'] ?? now()->toDateString(),
                'inspection_type' => $type->value,
                'status' => InspectionStatus::Pending->value,
                'source_type' => $source->getMorphClass(),
                'source_id' => $source->getKey(),
                'item_id' => $item->id,
                'batch_id' => $data['batch_id'] ?? null,
                'qc_template_id' => $template?->id,
                'quarantine_warehouse_id' => $data['quarantine_warehouse_id'] ?? null,
                'target_warehouse_id' => $data['target_warehouse_id'] ?? null,
                'lot_quantity' => $lotQty,
                'sample_size' => $sampleSize,
                'sampling_plan' => $template?->sampling_plan?->value,
                'sample_override_reason' => $data['sample_override_reason'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'inspector_id' => Auth::id(),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            if ($template) {
                foreach ($template->parameters as $index => $parameter) {
                    QcInspectionReading::query()->create([
                        'qc_inspection_id' => $inspection->id,
                        'qc_template_parameter_id' => $parameter->id,
                        'parameter_name' => $parameter->name,
                        'parameter_type' => $parameter->parameter_type->value,
                        'is_critical' => $parameter->is_critical,
                        'min_value' => $parameter->min_value,
                        'max_value' => $parameter->max_value,
                        'target_value' => $parameter->target_value,
                        'sort_order' => $index,
                    ]);
                }
            }

            return $this->repository->findById($inspection->id);
        });
    }

    /**
     * Save readings and optional quantities without completing.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): QcInspection
    {
        return DB::transaction(function () use ($id, $data): QcInspection {
            $inspection = $this->repository->findById($id);
            if (! $inspection->status->isEditable()) {
                throw ValidationException::withMessages(['inspection' => 'Completed inspections cannot be edited.']);
            }

            $readings = $data['readings'] ?? [];
            unset($data['readings']);

            $this->syncReadings($inspection, $readings);
            $overall = $this->evaluateOverall($inspection->fresh('readings'));

            $payload = [
                'status' => InspectionStatus::InProgress->value,
                'overall_result' => $overall,
                'updated_by' => Auth::id(),
                'inspector_id' => Auth::id(),
            ];

            foreach (['sample_size', 'sample_override_reason', 'accepted_qty', 'rejected_qty', 'rework_qty', 'disposition', 'deviation_note', 'remarks'] as $field) {
                if (array_key_exists($field, $data)) {
                    $payload[$field] = $data[$field];
                }
            }

            $this->repository->update($id, $payload);

            return $this->repository->findById($id);
        });
    }

    /**
     * Complete inspection and move stock per disposition (US-M10-01).
     *
     * @param  array<string, mixed>  $data
     */
    public function complete(int $id, array $data): QcInspection
    {
        return DB::transaction(function () use ($id, $data): QcInspection {
            $inspection = QcInspection::query()
                ->with(['readings', 'quarantineWarehouse'])
                ->lockForUpdate()
                ->findOrFail($id);
            if (! $inspection->status->isEditable()) {
                throw ValidationException::withMessages(['inspection' => 'Inspection is already completed.']);
            }

            if (! empty($data['readings'])) {
                $this->syncReadings($inspection, $data['readings']);
                $inspection->load('readings');
            }

            if ($inspection->readings->isEmpty()) {
                throw ValidationException::withMessages(['readings' => 'Record parameter readings before completing.']);
            }

            $overall = $this->evaluateOverall($inspection);
            $disposition = QcDisposition::from((string) $data['disposition']);

            if ($overall === 'fail' && $disposition === QcDisposition::Accept) {
                throw ValidationException::withMessages([
                    'disposition' => 'Critical failures cannot be overridden to Accept. Use Accept with deviation (with approval) or Reject.',
                ]);
            }

            if ($disposition === QcDisposition::AcceptWithDeviation) {
                if (empty($data['deviation_note'])) {
                    throw ValidationException::withMessages(['deviation_note' => 'Deviation justification is required.']);
                }
                if (! Auth::user()?->hasPermissionTo('qc_inspection.approve_deviation')) {
                    throw ValidationException::withMessages([
                        'disposition' => 'You do not have permission to accept with deviation.',
                    ]);
                }
            }

            $accepted = round((float) ($data['accepted_qty'] ?? 0), 4);
            $rejected = round((float) ($data['rejected_qty'] ?? 0), 4);
            $rework = round((float) ($data['rework_qty'] ?? 0), 4);
            $lot = (float) $inspection->lot_quantity;

            if (abs(($accepted + $rejected + $rework) - $lot) > 0.0001) {
                throw ValidationException::withMessages([
                    'accepted_qty' => 'Accepted + rejected + rework must equal the lot quantity.',
                ]);
            }

            $this->applyStockMoves($inspection, $disposition, $accepted, $rejected);

            $inspection->forceFill([
                'status' => InspectionStatus::Completed,
                'overall_result' => $overall,
                'disposition' => $disposition,
                'accepted_qty' => $accepted,
                'rejected_qty' => $rejected,
                'rework_qty' => $rework,
                'deviation_note' => $data['deviation_note'] ?? null,
                'deviation_approved_by' => $disposition === QcDisposition::AcceptWithDeviation ? Auth::id() : null,
                'deviation_approved_at' => $disposition === QcDisposition::AcceptWithDeviation ? now() : null,
                'remarks' => $data['remarks'] ?? $inspection->remarks,
                'inspector_id' => Auth::id(),
                'completed_at' => now(),
                'updated_by' => Auth::id(),
                'public_token' => $inspection->public_token ?: \Illuminate\Support\Str::random(40),
            ])->save();

            $this->activityLog->log(
                event: 'status_changed',
                description: "QC inspection {$inspection->document_no} completed as {$disposition->value}.",
                subject: $inspection,
                properties: [
                    'overall_result' => $overall,
                    'disposition' => $disposition->value,
                ],
                logName: 'qc'
            );

            return $this->repository->findById($id);
        });
    }

    public function delete(int $id): bool
    {
        $inspection = $this->repository->findById($id);
        if ($inspection->status === InspectionStatus::Completed) {
            throw ValidationException::withMessages(['inspection' => 'Completed inspections cannot be deleted.']);
        }

        return $this->repository->delete($id);
    }

    /**
     * @param  list<array<string, mixed>>  $readings
     */
    protected function syncReadings(QcInspection $inspection, array $readings): void
    {
        $existing = $inspection->readings->keyBy('id');

        foreach ($readings as $row) {
            $reading = isset($row['id']) ? $existing->get((int) $row['id']) : null;
            if ($reading === null) {
                continue;
            }

            $result = $this->evaluateReading($reading, $row);
            $reading->forceFill([
                'numeric_value' => $row['numeric_value'] ?? null,
                'pass_fail_value' => $row['pass_fail_value'] ?? null,
                'text_value' => $row['text_value'] ?? null,
                'result' => $result,
            ])->save();
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function evaluateReading(QcInspectionReading $reading, array $row): string
    {
        return match ($reading->parameter_type) {
            QcParameterType::Numeric => $this->evaluateNumeric($reading, $row),
            QcParameterType::PassFail => (($row['pass_fail_value'] ?? '') === 'pass') ? 'pass' : 'fail',
            QcParameterType::Text => 'pass',
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function evaluateNumeric(QcInspectionReading $reading, array $row): string
    {
        if (! isset($row['numeric_value']) || $row['numeric_value'] === '' || $row['numeric_value'] === null) {
            throw ValidationException::withMessages([
                'readings' => "Numeric reading required for {$reading->parameter_name}.",
            ]);
        }

        $value = (float) $row['numeric_value'];
        if ($reading->min_value !== null && $value < (float) $reading->min_value) {
            return 'fail';
        }
        if ($reading->max_value !== null && $value > (float) $reading->max_value) {
            return 'fail';
        }

        return 'pass';
    }

    protected function evaluateOverall(QcInspection $inspection): string
    {
        foreach ($inspection->readings as $reading) {
            if ($reading->is_critical && $reading->result === 'fail') {
                return 'fail';
            }
        }

        foreach ($inspection->readings as $reading) {
            if ($reading->result === 'fail') {
                return 'fail';
            }
        }

        return 'pass';
    }

    protected function applyStockMoves(
        QcInspection $inspection,
        QcDisposition $disposition,
        float $accepted,
        float $rejected
    ): void {
        $fromId = $inspection->quarantine_warehouse_id;
        if ($fromId === null) {
            return;
        }

        $rate = $this->ledger->averageRate((int) $inspection->item_id, (int) $fromId, $inspection->batch_id);

        $branchId = $inspection->quarantineWarehouse?->branch_id;

        if ($accepted > 0 && $disposition->movesToStore()) {
            $toId = $inspection->target_warehouse_id;
            if ($toId === null) {
                throw ValidationException::withMessages(['target_warehouse_id' => 'Target store warehouse is required.']);
            }
            $this->transfer($inspection, (int) $fromId, (int) $toId, $accepted, $rate, 'QC accept');
        }

        if ($rejected > 0 && ($disposition->movesToRejection() || $disposition === QcDisposition::AcceptWithDeviation)) {
            $rejection = $this->warehouses->rejectionWarehouse($branchId);
            $this->transfer($inspection, (int) $fromId, (int) $rejection->id, $rejected, $rate, 'QC reject');
        }

        // Rework qty stays in quarantine — no transfer.
    }

    protected function transfer(
        QcInspection $inspection,
        int $fromWarehouseId,
        int $toWarehouseId,
        float $qty,
        float $rate,
        string $remarks
    ): void {
        $this->ledger->post([
            'item_id' => $inspection->item_id,
            'warehouse_id' => $fromWarehouseId,
            'batch_id' => $inspection->batch_id,
            'transaction_type' => StockTransactionType::StockTransferOut,
            'posting_at' => now(),
            'qty_in' => 0,
            'qty_out' => $qty,
            'rate' => $rate,
            'source' => $inspection,
            'remarks' => $inspection->document_no.' '.$remarks,
        ]);

        $this->ledger->post([
            'item_id' => $inspection->item_id,
            'warehouse_id' => $toWarehouseId,
            'batch_id' => $inspection->batch_id,
            'transaction_type' => StockTransactionType::StockTransferIn,
            'posting_at' => now(),
            'qty_in' => $qty,
            'qty_out' => 0,
            'rate' => $rate,
            'source' => $inspection,
            'remarks' => $inspection->document_no.' '.$remarks,
        ]);
    }
}
