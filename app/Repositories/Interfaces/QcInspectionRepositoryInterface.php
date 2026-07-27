<?php

namespace App\Repositories\Interfaces;

use App\Models\QcInspection;

/**
 * QC inspection data-access contract (M10).
 */
interface QcInspectionRepositoryInterface
{
    public function findById(int $id): QcInspection;

    /** @param  array<string, mixed>  $data */
    public function create(array $data): QcInspection;

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): QcInspection;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
