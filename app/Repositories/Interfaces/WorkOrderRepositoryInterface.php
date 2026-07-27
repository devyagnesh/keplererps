<?php

namespace App\Repositories\Interfaces;

use App\Models\WorkOrder;

/**
 * Work order data-access contract (M09).
 */
interface WorkOrderRepositoryInterface
{
    public function findById(int $id): WorkOrder;

    /** @param  array<string, mixed>  $data */
    public function create(array $data): WorkOrder;

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): WorkOrder;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
