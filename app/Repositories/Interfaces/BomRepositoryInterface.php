<?php

namespace App\Repositories\Interfaces;

use App\Models\Bom;

/**
 * Data-access contract for Bill of Materials (M04).
 */
interface BomRepositoryInterface
{
    public function findById(int $id): Bom;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Bom;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Bom;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;

    public function nextVersionForItem(int $itemId): int;
}
