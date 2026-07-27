<?php

namespace App\Repositories\Eloquent;

use App\Models\Warehouse;
use App\Repositories\Interfaces\WarehouseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Eloquent implementation of WarehouseRepositoryInterface.
 */
class WarehouseRepository extends BaseRepository implements WarehouseRepositoryInterface
{
    public function __construct(Warehouse $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function all(array $filters = []): Collection
    {
        return Warehouse::query()
            ->with(['branch:id,name,code', 'parent:id,name,code'])
            ->when(! empty($filters['branch_id']), fn (Builder $q) => $q->where('branch_id', $filters['branch_id']))
            ->when(isset($filters['is_active']), fn (Builder $q) => $q->where('is_active', (bool) $filters['is_active']))
            ->orderBy('depth')
            ->orderBy('name')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): Warehouse
    {
        return Warehouse::query()->with(['branch', 'parent', 'children'])->findOrFail($id);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Warehouse
    {
        return Warehouse::query()->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): Warehouse
    {
        $warehouse = $this->findById($id);
        $warehouse->update($data);

        return $warehouse->fresh(['branch', 'parent']);
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
    public function hasChildren(int $id): bool
    {
        return Warehouse::query()->where('parent_id', $id)->exists();
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
        $orderColumnIndex = (int) data_get($params, 'order.0.column', 0);
        $orderDir = data_get($params, 'order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $columns = ['id', 'code', 'name', 'branch_id', 'level', 'is_active', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $base = Warehouse::query()->with(['branch:id,name', 'parent:id,name']);
        $recordsTotal = (clone $base)->count();

        $filtered = (clone $base)
            ->when($search !== '', function (Builder $q) use ($search): void {
                $q->where(function (Builder $inner) use ($search): void {
                    $inner->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when(! empty($params['branch_id']), fn (Builder $q) => $q->where('branch_id', $params['branch_id']))
            ->when(! empty($params['level']), fn (Builder $q) => $q->where('level', $params['level']));

        $recordsFiltered = (clone $filtered)->count();

        $rows = $filtered
            ->orderBy($orderColumn, $orderDir)
            ->skip($start)
            ->take($length > 0 ? $length : 25)
            ->get();

        $data = $rows->map(function (Warehouse $warehouse): array {
            return [
                'id' => $warehouse->id,
                'code' => $warehouse->code,
                'name' => $warehouse->name,
                'branch' => $warehouse->branch?->name ?? '—',
                'parent' => $warehouse->parent?->name ?? '—',
                'level' => ucfirst($warehouse->level->value),
                'is_active' => $warehouse->is_active
                    ? '<span class="badge bg-success-transparent">Active</span>'
                    : '<span class="badge bg-danger-transparent">Inactive</span>',
                'action' => view('admin.warehouses.partials.actions', ['warehouse' => $warehouse])->render(),
            ];
        })->all();

        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }
}
