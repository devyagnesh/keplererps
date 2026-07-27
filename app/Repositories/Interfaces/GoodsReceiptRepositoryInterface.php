<?php

namespace App\Repositories\Interfaces;

use App\Models\GoodsReceipt;

/**
 * Goods receipt data-access contract (M07).
 */
interface GoodsReceiptRepositoryInterface
{
    public function findById(int $id): GoodsReceipt;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): GoodsReceipt;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): GoodsReceipt;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
