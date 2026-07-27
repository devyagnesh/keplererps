<?php

namespace App\Repositories\Interfaces;

use App\Models\OpeningStock;

/**
 * Data-access contract for opening stock documents.
 */
interface OpeningStockRepositoryInterface
{
    public function findById(int $id): OpeningStock;

    /** @param  array<string, mixed>  $data */
    public function create(array $data): OpeningStock;

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): OpeningStock;

    public function delete(int $id): bool;

    public function nextDocumentNo(string $prefix = 'OS'): string;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
