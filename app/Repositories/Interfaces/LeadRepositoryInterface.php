<?php

namespace App\Repositories\Interfaces;

use App\Models\Lead;

/**
 * Data-access contract for the lead entity (M05).
 */
interface LeadRepositoryInterface
{
    public function findById(int $id): Lead;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Lead;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Lead;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;

    /**
     * Lead counts grouped by status, used for the pipeline summary.
     *
     * @return array<string, int>
     */
    public function countsByStatus(): array;
}
