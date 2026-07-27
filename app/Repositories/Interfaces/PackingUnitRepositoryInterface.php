<?php

namespace App\Repositories\Interfaces;

use App\Models\PackingUnit;
use Illuminate\Support\Collection;

/**
 * Data-access contract for the packing unit master (M17).
 */
interface PackingUnitRepositoryInterface
{
    public function findById(int $id): PackingUnit;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PackingUnit;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): PackingUnit;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;

    /**
     * Active packing units usable for an item (item-specific plus generic).
     *
     * @return Collection<int, PackingUnit>
     */
    public function selectableForItem(?int $itemId = null): Collection;
}
