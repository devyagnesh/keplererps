<?php

namespace App\Repositories\Interfaces;

use App\Models\LedgerAccount;

/**
 * Chart-of-accounts data-access contract.
 */
interface LedgerAccountRepositoryInterface
{
    public function findById(int $id): LedgerAccount;

    public function findByCode(string $code): ?LedgerAccount;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): LedgerAccount;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): LedgerAccount;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
