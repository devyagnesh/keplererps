<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\WorkCentre;
use App\Repositories\Interfaces\WorkCentreRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Asset register business logic (M11).
 */
class WorkCentreService
{
    public function __construct(protected WorkCentreRepositoryInterface $repository) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): WorkCentre
    {
        return $this->repository->findById($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): WorkCentre
    {
        return DB::transaction(function () use ($data): WorkCentre {
            $data = $this->normalize($data);
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            return $this->repository->create($data);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): WorkCentre
    {
        return DB::transaction(function () use ($id, $data): WorkCentre {
            $existing = $this->repository->findById($id);
            if (isset($data['code']) && strtoupper((string) $data['code']) !== $existing->code) {
                throw ValidationException::withMessages(['code' => 'Asset code is immutable once created.']);
            }
            unset($data['code']);
            $data = $this->normalize($data, $existing);
            $data['updated_by'] = Auth::id();

            return $this->repository->update($id, $data);
        });
    }

    public function delete(int $id): bool
    {
        $asset = $this->repository->findById($id);
        if ($asset->status->isStopped()) {
            throw ValidationException::withMessages(['asset' => 'Close open maintenance before deleting this asset.']);
        }

        return $this->repository->delete($id);
    }

    /**
     * @return Collection<int, WorkCentre>
     */
    public function dueForMaintenance(float $thresholdPercent = 90.0): Collection
    {
        return $this->repository->dueForMaintenance($thresholdPercent);
    }

    /**
     * @return Collection<int, WorkCentre>
     */
    public function statusBoard(): Collection
    {
        return $this->repository->statusBoard();
    }

    /**
     * Increment cycles / running hours from a posted production entry (M11).
     */
    public function recordProductionUsage(?int $workCentreId, float $goodQuantity, float $machineHours): void
    {
        if (! $workCentreId || $goodQuantity <= 0) {
            return;
        }

        $asset = WorkCentre::query()->lockForUpdate()->find($workCentreId);
        if ($asset === null) {
            return;
        }

        $cycles = $goodQuantity;
        if ($asset->cavity_count && (int) $asset->cavity_count > 0) {
            $cycles = (int) ceil($goodQuantity / (int) $asset->cavity_count);
        }

        $asset->forceFill([
            'cycles_used' => (int) $asset->cycles_used + (int) $cycles,
            'running_hours' => round((float) $asset->running_hours + max(0, $machineHours), 2),
            'updated_by' => Auth::id(),
        ])->save();
    }

    public function assertCanReceiveProduction(?int $workCentreId): void
    {
        if (! $workCentreId) {
            return;
        }

        $asset = WorkCentre::query()->find($workCentreId);
        if ($asset === null) {
            return;
        }

        if (! $asset->is_active || $asset->status === AssetStatus::Retired) {
            throw ValidationException::withMessages([
                'work_centre_id' => "Asset {$asset->code} is not active.",
            ]);
        }

        if (! $asset->status->canReceiveProduction()) {
            throw ValidationException::withMessages([
                'work_centre_id' => "Asset {$asset->code} is {$asset->status->label()} and cannot receive production.",
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalize(array $data, ?WorkCentre $existing = null): array
    {
        if (isset($data['code'])) {
            $data['code'] = strtoupper(trim((string) $data['code']));
        }

        $data['is_active'] = (bool) ($data['is_active'] ?? $existing?->is_active ?? true);
        $data['asset_type'] = $data['asset_type'] ?? $existing?->asset_type?->value ?? AssetType::Machine->value;
        $data['status'] = $data['status'] ?? $existing?->status?->value ?? AssetStatus::Active->value;
        $data['machine_rate_per_hour'] = $data['machine_rate_per_hour'] ?? $existing?->machine_rate_per_hour ?? 0;
        $data['labour_rate_per_hour'] = $data['labour_rate_per_hour'] ?? $existing?->labour_rate_per_hour ?? 0;

        $type = AssetType::from((string) $data['asset_type']);
        if (! $type->tracksCycles()) {
            $data['cavity_count'] = $data['cavity_count'] ?? null;
        }

        if (! empty($data['service_interval_days']) && empty($data['next_service_due_on'])) {
            $from = $existing?->last_service_at?->toDateString() ?? now()->toDateString();
            $data['next_service_due_on'] = now()->parse($from)
                ->addDays((int) $data['service_interval_days'])
                ->toDateString();
        }

        return $data;
    }
}
