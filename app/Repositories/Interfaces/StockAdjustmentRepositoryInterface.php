<?php

namespace App\Repositories\Interfaces;

use App\Models\StockAdjustment;

/**
 * Data-access contract for stock adjustments.
 */
interface StockAdjustmentRepositoryInterface
{
    public function findById(int $id): StockAdjustment;

    /** @param  array<string, mixed>  $data */
    public function create(array $data): StockAdjustment;

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): StockAdjustment;

    public function delete(int $id): bool;

    public function nextDocumentNo(string $prefix = 'ADJ'): string;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
