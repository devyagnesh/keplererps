<?php

namespace App\Repositories\Interfaces;

use App\Models\PurchaseReturn;

/**
 * Purchase return data-access contract.
 */
interface PurchaseReturnRepositoryInterface
{
    public function findById(int $id): PurchaseReturn;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PurchaseReturn;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): PurchaseReturn;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;

    /**
     * Quantity already returned per goods receipt line, excluding cancelled returns.
     *
     * @param  list<int>  $goodsReceiptItemIds
     * @return array<int, float>
     */
    public function returnedQtyByGrnItem(array $goodsReceiptItemIds, ?int $ignoreReturnId = null): array;
}
