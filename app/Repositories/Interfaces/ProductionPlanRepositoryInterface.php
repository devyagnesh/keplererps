<?php

namespace App\Repositories\Interfaces;

use App\Models\ProductionPlan;

/**
 * Production plan data-access contract.
 */
interface ProductionPlanRepositoryInterface
{
    public function findById(int $id): ProductionPlan;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ProductionPlan;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): ProductionPlan;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
