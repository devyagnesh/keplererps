<?php

namespace App\Repositories\Interfaces;

use App\Models\SalaryRun;

/**
 * Data-access contract for payroll runs (M14).
 */
interface SalaryRunRepositoryInterface
{
    public function findById(int $id): SalaryRun;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SalaryRun;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): SalaryRun;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;

    /**
     * The run that already owns a period, ignoring cancelled ones.
     */
    public function findOpenForPeriod(int $year, int $month, ?int $ignoreId = null): ?SalaryRun;
}
