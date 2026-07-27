<?php

namespace App\Repositories\Interfaces;

use App\Models\Warehouse;
use Illuminate\Support\Collection;

/**
 * Data-access contract for warehouses.
 */
interface WarehouseRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Warehouse>
     */
    public function all(array $filters = []): Collection;

    public function findById(int $id): Warehouse;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Warehouse;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Warehouse;

    public function delete(int $id): bool;

    public function hasChildren(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
