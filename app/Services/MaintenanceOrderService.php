<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\DocumentSeriesType;
use App\Enums\MaintenanceOrderStatus;
use App\Enums\MaintenanceOrderType;
use App\Enums\StockTransactionType;
use App\Models\MaintenanceOrder;
use App\Models\MaintenanceOrderPart;
use App\Models\WorkCentre;
use App\Repositories\Interfaces\MaintenanceOrderRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Maintenance order create / close with spare-part stock issues (M11).
 */
class MaintenanceOrderService
{
    public function __construct(
        protected MaintenanceOrderRepositoryInterface $repository,
        protected StockLedgerService $ledger,
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

    public function find(int $id): MaintenanceOrder
    {
        return $this->repository->findById($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): MaintenanceOrder
    {
        return DB::transaction(function () use ($data): MaintenanceOrder {
            $parts = $data['parts'] ?? [];
            unset($data['parts']);

            $type = MaintenanceOrderType::from((string) $data['order_type']);
            $asset = WorkCentre::query()->lockForUpdate()->findOrFail((int) $data['work_centre_id']);

            if ($asset->status === AssetStatus::Retired) {
                throw ValidationException::withMessages(['work_centre_id' => 'Retired assets cannot be maintained.']);
            }

            if ($asset->status->isStopped()) {
                throw ValidationException::withMessages([
                    'work_centre_id' => "Asset {$asset->code} already has an open stoppage ({$asset->status->label()}).",
                ]);
            }

            $order = $this->repository->create([
                'document_no' => $this->numbering->next(DocumentSeriesType::MaintenanceOrder),
                'document_date' => $data['document_date'] ?? now()->toDateString(),
                'order_type' => $type->value,
                'status' => MaintenanceOrderStatus::Open->value,
                'work_centre_id' => $asset->id,
                'opened_at' => now(),
                'reason' => $data['reason'] ?? null,
                'action_taken' => $data['action_taken'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'reported_by' => Auth::id(),
                'assigned_to' => $data['assigned_to'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $this->syncParts($order, $parts);

            $asset->forceFill([
                'status' => $type->assetStatusWhileOpen()->value,
                'updated_by' => Auth::id(),
            ])->save();

            $this->activityLog->log(
                event: 'status_changed',
                description: "Maintenance {$order->document_no} opened ({$type->value}) for {$asset->code}.",
                subject: $order,
                properties: ['asset_status' => $asset->status->value],
                logName: 'maintenance'
            );

            return $this->repository->findById($order->id);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): MaintenanceOrder
    {
        return DB::transaction(function () use ($id, $data): MaintenanceOrder {
            $order = MaintenanceOrder::query()->lockForUpdate()->findOrFail($id);
            if (! $order->status->isEditable()) {
                throw ValidationException::withMessages(['order' => 'Closed maintenance orders cannot be edited.']);
            }

            $parts = $data['parts'] ?? null;
            unset($data['parts'], $data['document_no'], $data['order_type'], $data['work_centre_id']);

            $payload = [
                'document_date' => $data['document_date'] ?? $order->document_date?->toDateString(),
                'reason' => $data['reason'] ?? $order->reason,
                'action_taken' => $data['action_taken'] ?? $order->action_taken,
                'remarks' => $data['remarks'] ?? $order->remarks,
                'assigned_to' => $data['assigned_to'] ?? $order->assigned_to,
                'status' => isset($data['status'])
                    ? MaintenanceOrderStatus::from((string) $data['status'])->value
                    : $order->status->value,
                'updated_by' => Auth::id(),
            ];

            if (($payload['status'] ?? null) === MaintenanceOrderStatus::InProgress->value
                && $order->status === MaintenanceOrderStatus::Open) {
                $payload['status'] = MaintenanceOrderStatus::InProgress->value;
            }

            $order->forceFill($payload)->save();

            if (is_array($parts)) {
                $order->parts()->where('issued', false)->delete();
                $this->syncParts($order->fresh('parts'), $parts);
            }

            return $this->repository->findById($id);
        });
    }

    /**
     * Issue unissued spare parts to stock (US-M11-04).
     */
    public function issueParts(int $id): MaintenanceOrder
    {
        return DB::transaction(function () use ($id): MaintenanceOrder {
            $order = MaintenanceOrder::query()->with('parts')->lockForUpdate()->findOrFail($id);
            if (! $order->status->isEditable()) {
                throw ValidationException::withMessages(['order' => 'Cannot issue parts on a closed order.']);
            }

            foreach ($order->parts as $part) {
                if ($part->issued) {
                    continue;
                }

                $this->issuePartLine($order, $part);
            }

            if ($order->status === MaintenanceOrderStatus::Open) {
                $order->forceFill([
                    'status' => MaintenanceOrderStatus::InProgress,
                    'updated_by' => Auth::id(),
                ])->save();
            }

            return $this->repository->findById($id);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function close(int $id, array $data = []): MaintenanceOrder
    {
        return DB::transaction(function () use ($id, $data): MaintenanceOrder {
            $order = MaintenanceOrder::query()
                ->with(['parts', 'workCentre'])
                ->lockForUpdate()
                ->findOrFail($id);

            if (! $order->status->isEditable()) {
                throw ValidationException::withMessages(['order' => 'Maintenance order is already closed.']);
            }

            if ($order->parts->contains(fn (MaintenanceOrderPart $p): bool => ! $p->issued)) {
                $this->issueUnissuedParts($order);
                $order->load('parts');
            }

            $closedAt = now();
            $openedAt = $order->opened_at ?? $order->created_at ?? $closedAt;
            $downtimeMinutes = (int) ($data['downtime_minutes'] ?? max(0, $openedAt->diffInMinutes($closedAt)));
            $rate = (float) ($order->workCentre?->machine_rate_per_hour ?? 0);
            $downtimeCost = round(($downtimeMinutes / 60) * $rate, 2);

            $order->forceFill([
                'status' => MaintenanceOrderStatus::Closed,
                'closed_at' => $closedAt,
                'action_taken' => $data['action_taken'] ?? $order->action_taken,
                'remarks' => $data['remarks'] ?? $order->remarks,
                'downtime_minutes' => $downtimeMinutes,
                'downtime_cost' => $downtimeCost,
                'updated_by' => Auth::id(),
            ])->save();

            $asset = WorkCentre::query()->lockForUpdate()->findOrFail($order->work_centre_id);
            $asset->forceFill([
                'status' => AssetStatus::Active,
                'last_service_at' => $closedAt,
                'cycles_at_last_service' => $asset->cycles_used,
                'hours_at_last_service' => $asset->running_hours,
                'next_service_due_on' => $asset->service_interval_days
                    ? $closedAt->copy()->addDays((int) $asset->service_interval_days)->toDateString()
                    : $asset->next_service_due_on,
                'updated_by' => Auth::id(),
            ])->save();

            $this->activityLog->log(
                event: 'status_changed',
                description: "Maintenance {$order->document_no} closed. Downtime {$downtimeMinutes} min.",
                subject: $order,
                properties: [
                    'downtime_minutes' => $downtimeMinutes,
                    'downtime_cost' => $downtimeCost,
                ],
                logName: 'maintenance'
            );

            return $this->repository->findById($id);
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $order = MaintenanceOrder::query()->with('parts')->lockForUpdate()->findOrFail($id);
            if ($order->status === MaintenanceOrderStatus::Closed) {
                throw ValidationException::withMessages(['order' => 'Closed maintenance orders cannot be deleted.']);
            }

            if ($order->parts->contains(fn (MaintenanceOrderPart $p): bool => $p->issued)) {
                throw ValidationException::withMessages(['order' => 'Cancel is blocked after spare parts were issued. Close the order instead.']);
            }

            $asset = WorkCentre::query()->lockForUpdate()->findOrFail($order->work_centre_id);
            $order->forceFill([
                'status' => MaintenanceOrderStatus::Cancelled,
                'updated_by' => Auth::id(),
            ])->save();
            $order->delete();

            $stillOpen = MaintenanceOrder::query()
                ->where('work_centre_id', $asset->id)
                ->whereIn('status', [MaintenanceOrderStatus::Open->value, MaintenanceOrderStatus::InProgress->value])
                ->exists();

            if (! $stillOpen && $asset->status->isStopped()) {
                $asset->forceFill([
                    'status' => AssetStatus::Active,
                    'updated_by' => Auth::id(),
                ])->save();
            }

            return true;
        });
    }

    protected function issueUnissuedParts(MaintenanceOrder $order): void
    {
        foreach ($order->parts as $part) {
            if ($part->issued) {
                continue;
            }
            $this->issuePartLine($order, $part);
        }
    }

    protected function issuePartLine(MaintenanceOrder $order, MaintenanceOrderPart $part): void
    {
        $rate = $this->ledger->averageRate((int) $part->item_id, (int) $part->warehouse_id);
        $qty = (float) $part->quantity;

        $this->ledger->post([
            'item_id' => $part->item_id,
            'warehouse_id' => $part->warehouse_id,
            'transaction_type' => StockTransactionType::MaintenanceIssue,
            'posting_at' => now(),
            'qty_in' => 0,
            'qty_out' => $qty,
            'rate' => $rate,
            'source' => $order,
            'remarks' => $order->document_no.' spare issue',
        ]);

        $part->forceFill([
            'rate' => $rate,
            'amount' => round($qty * $rate, 2),
            'issued' => true,
            'issued_at' => now(),
        ])->save();
    }

    /**
     * @param  list<array<string, mixed>>  $parts
     */
    protected function syncParts(MaintenanceOrder $order, array $parts): void
    {
        foreach (array_values($parts) as $index => $row) {
            if (empty($row['item_id']) || empty($row['warehouse_id']) || empty($row['quantity'])) {
                continue;
            }

            MaintenanceOrderPart::query()->create([
                'maintenance_order_id' => $order->id,
                'item_id' => (int) $row['item_id'],
                'warehouse_id' => (int) $row['warehouse_id'],
                'quantity' => round((float) $row['quantity'], 4),
                'rate' => 0,
                'amount' => 0,
                'issued' => false,
                'sort_order' => $index,
            ]);
        }
    }
}
