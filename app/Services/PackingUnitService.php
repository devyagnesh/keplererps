<?php

namespace App\Services;

use App\Models\PackingUnit;
use App\Repositories\Interfaces\PackingUnitRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Packing unit master business logic (M17).
 */
class PackingUnitService
{
    public function __construct(protected PackingUnitRepositoryInterface $repository) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): PackingUnit
    {
        return $this->repository->findById($id);
    }

    /**
     * @return Collection<int, PackingUnit>
     */
    public function selectableForItem(?int $itemId = null): Collection
    {
        return $this->repository->selectableForItem($itemId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PackingUnit
    {
        $data['code'] = strtoupper(trim((string) $data['code']));

        return $this->repository->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): PackingUnit
    {
        if (isset($data['code'])) {
            $data['code'] = strtoupper(trim((string) $data['code']));
        }

        $this->assertNoCycle($id, isset($data['parent_id']) ? (int) $data['parent_id'] : null);

        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        $unit = $this->repository->findById($id);

        if ($unit->children()->exists()) {
            throw ValidationException::withMessages([
                'packing_unit' => 'Remove the nested packing units first.',
            ]);
        }

        if ($unit->packages()->exists()) {
            throw ValidationException::withMessages([
                'packing_unit' => 'This packing unit is used by printed labels. Mark it inactive instead.',
            ]);
        }

        return $this->repository->delete($id);
    }

    /**
     * Reject a parent that would make the nesting chain loop back on itself.
     */
    protected function assertNoCycle(int $id, ?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        if ($parentId === $id) {
            throw ValidationException::withMessages([
                'parent_id' => 'A packing unit cannot be its own parent.',
            ]);
        }

        $parent = PackingUnit::query()->find($parentId);
        $depth = 0;

        while ($parent !== null && $depth < PackingUnit::MAX_NESTING_DEPTH) {
            if ((int) $parent->id === $id) {
                throw ValidationException::withMessages([
                    'parent_id' => 'That parent would create a circular packing hierarchy.',
                ]);
            }

            $parent = $parent->parent;
            $depth++;
        }
    }
}
