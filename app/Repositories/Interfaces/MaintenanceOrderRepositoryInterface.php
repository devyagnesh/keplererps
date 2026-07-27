<?php

namespace App\Repositories\Interfaces;

use App\Models\MaintenanceOrder;

/**
 * Maintenance order data-access contract (M11).
 */
interface MaintenanceOrderRepositoryInterface
{
    public function findById(int $id): MaintenanceOrder;

    /** @param  array<string, mixed>  $data */
    public function create(array $data): MaintenanceOrder;

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): MaintenanceOrder;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
