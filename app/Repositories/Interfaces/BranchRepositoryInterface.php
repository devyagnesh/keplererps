<?php

namespace App\Repositories\Interfaces;

use App\Models\Branch;
use Illuminate\Support\Collection;

/**
 * Data-access contract for branches.
 */
interface BranchRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Branch>
     */
    public function all(array $filters = []): Collection;

    public function findById(int $id): Branch;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Branch;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Branch;

    public function delete(int $id): bool;

    /**
     * Server-side DataTables payload.
     *
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;

    /**
     * Active branches for selects.
     *
     * @return Collection<int, Branch>
     */
    public function activeOptions(): Collection;
}
