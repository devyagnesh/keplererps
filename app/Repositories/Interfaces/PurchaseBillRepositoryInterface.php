<?php

namespace App\Repositories\Interfaces;

use App\Models\PurchaseBill;

/**
 * Purchase bill data-access contract (US-M07-04).
 */
interface PurchaseBillRepositoryInterface
{
    public function findById(int $id): PurchaseBill;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PurchaseBill;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): PurchaseBill;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
