<?php

namespace App\Repositories\Interfaces;

use App\Models\Transporter;

/**
 * Data-access contract for transporters.
 */
interface TransporterRepositoryInterface
{
    public function findById(int $id): Transporter;

    /** @param  array<string, mixed>  $data */
    public function create(array $data): Transporter;

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): Transporter;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
