<?php

namespace App\Repositories\Interfaces;

use App\Models\Opportunity;
use Illuminate\Support\Collection;

/**
 * Data-access contract for the opportunity entity (M05).
 */
interface OpportunityRepositoryInterface
{
    public function findById(int $id): Opportunity;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Opportunity;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Opportunity;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;

    /**
     * Open opportunities grouped by stage for the pipeline board.
     *
     * @return Collection<string, Collection<int, Opportunity>>
     */
    public function groupedByStage(?int $assignedUserId = null): Collection;
}
