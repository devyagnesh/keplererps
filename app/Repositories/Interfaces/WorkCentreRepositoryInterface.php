<?php

namespace App\Repositories\Interfaces;

use App\Models\WorkCentre;
use Illuminate\Database\Eloquent\Collection;

/**
 * Work centre / asset data-access contract (M11).
 */
interface WorkCentreRepositoryInterface
{
    public function findById(int $id): WorkCentre;

    /** @param  array<string, mixed>  $data */
    public function create(array $data): WorkCentre;

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): WorkCentre;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;

    /**
     * @return Collection<int, WorkCentre>
     */
    public function dueForMaintenance(float $thresholdPercent = 90.0): Collection;

    /**
     * @return Collection<int, WorkCentre>
     */
    public function statusBoard(): Collection;
}
