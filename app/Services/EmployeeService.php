<?php

namespace App\Services;

use App\Enums\DocumentSeriesType;
use App\Enums\EmploymentStatus;
use App\Models\Employee;
use App\Repositories\Interfaces\EmployeeRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Employee master business rules (M14).
 */
class EmployeeService
{
    public function __construct(
        protected EmployeeRepositoryInterface $repository,
        protected NumberingService $numbering
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): Employee
    {
        return $this->repository->findById($id);
    }

    /**
     * @return Collection<int, Employee>
     */
    public function selectable(): Collection
    {
        return $this->repository->selectable();
    }

    /**
     * @return Collection<int, Employee>
     */
    public function payableOn(string $date): Collection
    {
        return $this->repository->payableOn($date);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Employee
    {
        return DB::transaction(function () use ($data): Employee {
            $data['employee_code'] = $this->numbering->next(DocumentSeriesType::Employee);
            $this->assertExitDateConsistent($data);

            return $this->repository->findById($this->repository->create($data)->id);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Employee
    {
        return DB::transaction(function () use ($id, $data): Employee {
            $employee = $this->repository->findById($id);

            // The code is system-generated and stays with the employee for life.
            unset($data['employee_code']);

            $this->assertExitDateConsistent(array_merge([
                'date_of_joining' => $employee->date_of_joining?->toDateString(),
            ], $data));

            return $this->repository->update($id, $data);
        });
    }

    /**
     * Employees are soft-deleted only while they carry no payroll or attendance history.
     */
    public function delete(int $id): bool
    {
        $employee = $this->repository->findById($id);

        if ($employee->salarySlips()->exists()) {
            throw ValidationException::withMessages([
                'employee' => 'This employee already appears on a salary run and cannot be deleted. Mark them resigned instead.',
            ]);
        }

        if ($employee->attendance()->exists()) {
            throw ValidationException::withMessages([
                'employee' => 'Attendance has been marked for this employee. Mark them resigned instead of deleting.',
            ]);
        }

        return $this->repository->delete($id);
    }

    /**
     * An exit date is mandatory once the employee has left, and cannot precede joining.
     *
     * @param  array<string, mixed>  $data
     */
    protected function assertExitDateConsistent(array $data): void
    {
        $status = $data['status'] ?? null;
        $status = $status instanceof EmploymentStatus ? $status : EmploymentStatus::tryFrom((string) $status);
        $exit = $data['date_of_exit'] ?? null;

        if ($status !== null && ! $status->isPayable() && empty($exit)) {
            throw ValidationException::withMessages([
                'date_of_exit' => 'Record the exit date when an employee has resigned or been terminated.',
            ]);
        }

        if (! empty($exit) && ! empty($data['date_of_joining']) && $exit < $data['date_of_joining']) {
            throw ValidationException::withMessages([
                'date_of_exit' => 'The exit date cannot fall before the joining date.',
            ]);
        }
    }
}
