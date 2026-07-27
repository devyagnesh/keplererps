<?php

namespace App\Repositories\Interfaces;

use App\Models\User;

/**
 * Data-access contract for users.
 */
interface UserRepositoryInterface
{
    public function findById(int $id): User;

    /** @param  array<string, mixed>  $data */
    public function create(array $data): User;

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): User;

    public function delete(int $id): bool;

    public function countActiveSuperAdmins(?int $exceptUserId = null): int;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
