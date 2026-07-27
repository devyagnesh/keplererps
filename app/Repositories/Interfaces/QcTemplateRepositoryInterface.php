<?php

namespace App\Repositories\Interfaces;

use App\Models\QcTemplate;

/**
 * QC template data-access contract (M10).
 */
interface QcTemplateRepositoryInterface
{
    public function findById(int $id): QcTemplate;

    /** @param  array<string, mixed>  $data */
    public function create(array $data): QcTemplate;

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): QcTemplate;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
