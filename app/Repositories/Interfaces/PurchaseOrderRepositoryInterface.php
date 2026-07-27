<?php

namespace App\Repositories\Interfaces;

use App\Models\PurchaseOrder;

/**
 * Purchase order data-access contract (M07).
 */
interface PurchaseOrderRepositoryInterface
{
    public function findById(int $id): PurchaseOrder;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PurchaseOrder;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): PurchaseOrder;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
