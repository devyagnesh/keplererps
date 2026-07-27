<?php

namespace App\Repositories\Interfaces;

use App\Models\FinancialYear;

/**
 * Data-access contract for financial years.
 */
interface FinancialYearRepositoryInterface
{
    public function findById(int $id): FinancialYear;

    public function current(): ?FinancialYear;

    /** @param  array<string, mixed>  $data */
    public function create(array $data): FinancialYear;

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): FinancialYear;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
