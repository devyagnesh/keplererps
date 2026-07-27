<?php

namespace App\Repositories\Interfaces;

use App\Models\Employee;
use Illuminate\Support\Collection;

/**
 * Data-access contract for the employee master (M14).
 */
interface EmployeeRepositoryInterface
{
    public function findById(int $id): Employee;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Employee;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Employee;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;

    /**
     * Employees on the rolls for the given date, ordered by code.
     *
     * @return Collection<int, Employee>
     */
    public function payableOn(string $date): Collection;

    /**
     * Active employees for pickers.
     *
     * @return Collection<int, Employee>
     */
    public function selectable(): Collection;
}
