<?php

namespace App\Repositories\Interfaces;

use App\Models\Role;
use Illuminate\Support\Collection;

/**
 * Data-access contract for roles.
 */
interface RoleRepositoryInterface
{
    public function findById(int $id): Role;

    /** @param  array<string, mixed>  $data */
    public function create(array $data): Role;

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): Role;

    public function delete(int $id): bool;

    /** @return Collection<int, Role> */
    public function activeOptions(): Collection;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
