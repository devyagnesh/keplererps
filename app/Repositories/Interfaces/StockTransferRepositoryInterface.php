<?php

namespace App\Repositories\Interfaces;

use App\Models\StockTransfer;

/**
 * Data-access contract for stock transfers.
 */
interface StockTransferRepositoryInterface
{
    public function findById(int $id): StockTransfer;

    /** @param  array<string, mixed>  $data */
    public function create(array $data): StockTransfer;

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): StockTransfer;

    public function delete(int $id): bool;

    public function nextDocumentNo(string $prefix = 'TRF'): string;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
