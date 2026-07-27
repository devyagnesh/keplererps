<?php

namespace App\Repositories\Eloquent;

use App\Models\Branch;
use App\Repositories\Interfaces\BranchRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Eloquent implementation of BranchRepositoryInterface.
 */
class BranchRepository extends BaseRepository implements BranchRepositoryInterface
{
    public function __construct(Branch $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function all(array $filters = []): Collection
    {
        return Branch::query()
            ->with('state:id,name,code')
            ->when(isset($filters['is_active']), fn (Builder $q) => $q->where('is_active', (bool) $filters['is_active']))
            ->latest('id')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): Branch
    {
        return Branch::query()->with('state')->findOrFail($id);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Branch
    {
        return Branch::query()->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): Branch
    {
        $branch = $this->findById($id);
        $branch->update($data);

        return $branch->fresh(['state']);
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
    public function activeOptions(): Collection
    {
        return Branch::query()
            ->select(['id', 'code', 'name'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
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
        $columns = ['id', 'code', 'name', 'state_id', 'is_head_office', 'is_active', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $base = Branch::query()->with('state:id,name');
        $recordsTotal = (clone $base)->count();

        $filtered = (clone $base)
            ->when($search !== '', function (Builder $q) use ($search): void {
                $q->where(function (Builder $inner) use ($search): void {
                    $inner->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when(isset($params['is_active']) && $params['is_active'] !== '', fn (Builder $q) => $q->where('is_active', (bool) $params['is_active']));

        $recordsFiltered = (clone $filtered)->count();

        $rows = $filtered
            ->orderBy($orderColumn, $orderDir)
            ->skip($start)
            ->take($length > 0 ? $length : 25)
            ->get();

        $data = $rows->map(function (Branch $branch): array {
            return [
                'id' => $branch->id,
                'code' => $branch->code,
                'name' => $branch->name,
                'state' => $branch->state?->name ?? '—',
                'is_head_office' => $branch->is_head_office
                    ? '<span class="badge bg-primary-transparent">HO</span>'
                    : '<span class="badge bg-light text-muted">Branch</span>',
                'is_active' => $branch->is_active
                    ? '<span class="badge bg-success-transparent">Active</span>'
                    : '<span class="badge bg-danger-transparent">Inactive</span>',
                'created_at' => $branch->created_at?->format('d M Y'),
                'action' => view('admin.branches.partials.actions', ['branch' => $branch])->render(),
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
