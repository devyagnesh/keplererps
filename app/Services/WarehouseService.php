<?php

namespace App\Services;

use App\Enums\WarehouseLevel;
use App\Models\Warehouse;
use App\Repositories\Interfaces\WarehouseRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Business logic for warehouse hierarchy (M01 / US-M01-02 / BR-04).
 */
class WarehouseService
{
    public function __construct(
        protected WarehouseRepositoryInterface $repository
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): Warehouse
    {
        return $this->repository->findById($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Warehouse
    {
        return DB::transaction(function () use ($data): Warehouse {
            $data = $this->normalizeHierarchy($data);

            return $this->repository->create($data);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Warehouse
    {
        return DB::transaction(function () use ($id, $data): Warehouse {
            $data = $this->normalizeHierarchy($data, $id);

            return $this->repository->update($id, $data);
        });
    }

    /**
     * Soft-delete when the warehouse has no child nodes and no stock balances.
     *
     * @throws ValidationException
     */
    public function delete(int $id): bool
    {
        if ($this->repository->hasChildren($id)) {
            throw ValidationException::withMessages([
                'warehouse' => 'A parent warehouse cannot be deleted while children exist.',
            ]);
        }

        $warehouse = $this->repository->findById($id);
        if ($warehouse->is_system) {
            throw ValidationException::withMessages([
                'warehouse' => 'System warehouses cannot be deleted.',
            ]);
        }

        if (\App\Models\StockBalance::query()->where('warehouse_id', $id)->where('qty', '>', 0)->exists()) {
            throw ValidationException::withMessages([
                'warehouse' => 'This warehouse still has stock and cannot be deleted.',
            ]);
        }

        return $this->repository->delete($id);
    }

    /**
     * Validate and set depth from level / parent.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    protected function normalizeHierarchy(array $data, ?int $exceptId = null): array
    {
        $data['code'] = strtoupper(trim((string) $data['code']));
        $level = WarehouseLevel::from((string) $data['level']);
        $data['depth'] = $level->depth();

        if ($level === WarehouseLevel::Plant) {
            $data['parent_id'] = null;
        } else {
            if (empty($data['parent_id'])) {
                throw ValidationException::withMessages([
                    'parent_id' => 'A parent warehouse is required for this level.',
                ]);
            }

            $parent = $this->repository->findById((int) $data['parent_id']);
            if ($exceptId !== null && (int) $data['parent_id'] === $exceptId) {
                throw ValidationException::withMessages([
                    'parent_id' => 'A warehouse cannot be its own parent.',
                ]);
            }

            if ((int) $parent->branch_id !== (int) $data['branch_id']) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Parent warehouse must belong to the same branch.',
                ]);
            }

            if ($parent->depth >= 4 || $data['depth'] !== $parent->depth + 1) {
                throw ValidationException::withMessages([
                    'level' => 'Warehouse hierarchy must follow Plant → Store → Rack → Bin (max 4 levels).',
                ]);
            }

            $parent->forceFill(['is_leaf' => false])->save();
        }

        $data['is_leaf'] = $exceptId === null
            ? true
            : ! $this->repository->hasChildren($exceptId);

        $data['warehouse_type'] = $data['warehouse_type'] ?? 'store';
        $data['allow_negative_stock'] = (bool) ($data['allow_negative_stock'] ?? false);

        return $data;
    }
}
