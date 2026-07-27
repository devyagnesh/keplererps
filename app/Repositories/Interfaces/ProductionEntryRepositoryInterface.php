<?php

namespace App\Repositories\Interfaces;

use App\Models\ProductionEntry;

/**
 * Production entry data-access contract (M09).
 */
interface ProductionEntryRepositoryInterface
{
    public function findById(int $id): ProductionEntry;

    /** @param  array<string, mixed>  $data */
    public function create(array $data): ProductionEntry;

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): ProductionEntry;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
