<?php

namespace App\Repositories\Eloquent;

use App\Enums\AssetStatus;
use App\Models\WorkCentre;
use App\Repositories\Interfaces\WorkCentreRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Eloquent work centre / asset repository.
 */
class WorkCentreRepository implements WorkCentreRepositoryInterface
{
    public function __construct(protected WorkCentre $model) {}

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): WorkCentre
    {
        return $this->model->newQuery()->findOrFail($id);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): WorkCentre
    {
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        return $this->model->newQuery()->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): WorkCentre
    {
        $asset = $this->findById($id);
        $data['updated_by'] = Auth::id();
        $asset->update($data);

        return $asset->fresh();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        return (bool) $this->findById($id)->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function getForDataTable(array $params): array
    {
        $draw = (int) ($params['draw'] ?? 1);
        $start = (int) ($params['start'] ?? 0);
        $length = (int) ($params['length'] ?? 25);
        $search = trim((string) data_get($params, 'search.value', ''));
        $orderCol = (int) data_get($params, 'order.0.column', 0);
        $orderDir = strtolower((string) data_get($params, 'order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $columns = ['id', 'code', 'name', 'asset_type', 'status', 'is_active', 'id'];
        $orderBy = $columns[$orderCol] ?? 'id';

        $base = $this->model->newQuery();
        $recordsTotal = (clone $base)->count();

        if ($search !== '') {
            $base->where(function ($q) use ($search): void {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('serial_no', 'like', "%{$search}%");
            });
        }

        if (! empty($params['status'])) {
            $base->where('status', $params['status']);
        }

        if (! empty($params['asset_type'])) {
            $base->where('asset_type', $params['asset_type']);
        }

        $recordsFiltered = (clone $base)->count();
        $rows = $base->orderBy($orderBy, $orderDir)->skip($start)->take($length)->get();

        $data = $rows->map(function (WorkCentre $row): array {
            return [
                'id' => $row->id,
                'code' => e($row->code),
                'name' => e($row->name),
                'asset_type' => e($row->asset_type->label()),
                'status' => e($row->status->label()),
                'location' => e($row->location ?? '—'),
                'cycles' => number_format((int) $row->cycles_used).($row->life_cycles ? ' / '.number_format((int) $row->life_cycles) : ''),
                'is_active' => $row->is_active ? 'Active' : 'Inactive',
                'action' => view('admin.work-centres.partials.actions', ['asset' => $row])->render(),
            ];
        })->all();

        return compact('draw', 'recordsTotal', 'recordsFiltered', 'data');
    }

    /**
     * {@inheritdoc}
     */
    public function dueForMaintenance(float $thresholdPercent = 90.0): Collection
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->where('status', '!=', AssetStatus::Retired->value)
            ->where(function ($q): void {
                $q->whereNotNull('service_interval_days')
                    ->orWhereNotNull('service_interval_hours')
                    ->orWhereNotNull('service_interval_cycles');
            })
            ->orderBy('code')
            ->get()
            ->filter(fn (WorkCentre $asset): bool => $asset->isMaintenanceDue($thresholdPercent))
            ->values();
    }

    /**
     * {@inheritdoc}
     */
    public function statusBoard(): Collection
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->where('status', '!=', AssetStatus::Retired->value)
            ->withCount([
                'workOrders as open_work_orders_count' => fn ($q) => $q->whereIn('status', [
                    'released', 'in_progress',
                ]),
            ])
            ->orderBy('status')
            ->orderBy('code')
            ->get();
    }
}
