<?php

namespace App\Repositories\Interfaces;

use App\Models\Uom;

/**
 * Data-access contract for units of measure.
 */
interface UomRepositoryInterface
{
    public function findById(int $id): Uom;

    /** @param  array<string, mixed>  $data */
    public function create(array $data): Uom;

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): Uom;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
