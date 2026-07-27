<?php

namespace App\Repositories\Eloquent;

use App\Enums\EmploymentStatus;
use App\Models\Employee;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\EmployeeRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Eloquent employee repository.
 */
class EmployeeRepository implements EmployeeRepositoryInterface
{
    use BuildsServerSideDataTable;

    /**
     * @var list<string>
     */
    protected const EAGER = ['shift:id,code,name', 'branch:id,code,name'];

    public function findById(int $id): Employee
    {
        return Employee::query()->with(self::EAGER)->findOrFail($id);
    }

    public function create(array $data): Employee
    {
        return Employee::query()->create($data);
    }

    public function update(int $id, array $data): Employee
    {
        $employee = Employee::query()->findOrFail($id);
        $employee->update($data);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        return (bool) Employee::query()->findOrFail($id)->delete();
    }

    public function getForDataTable(array $params): array
    {
        $query = Employee::query()->with(self::EAGER);

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }
        if (! empty($params['shift_id'])) {
            $query->where('shift_id', (int) $params['shift_id']);
        }
        if (! empty($params['department'])) {
            $query->where('department', $params['department']);
        }

        return $this->buildDataTable(
            $query,
            ['id', 'employee_code', 'full_name', 'department', 'date_of_joining', 'monthly_gross', 'status'],
            ['employee_code', 'full_name', 'designation', 'department', 'mobile'],
            fn (Employee $employee): array => [
                'id' => $employee->id,
                'employee_code' => e($employee->employee_code),
                'full_name' => e($employee->full_name),
                'designation' => e($employee->designation ?? '—'),
                'department' => e($employee->department ?? '—'),
                'shift' => e($employee->shift?->code ?? '—'),
                'date_of_joining' => $employee->date_of_joining?->format('Y-m-d') ?? '—',
                'monthly_gross' => number_format((float) $employee->monthly_gross, 2),
                'status' => '<span class="badge '.$employee->status->badgeClass().'">'.$employee->status->label().'</span>',
                'action' => view('admin.employees.partials.actions', ['employee' => $employee])->render(),
            ],
            $params
        );
    }

    public function payableOn(string $date): Collection
    {
        return Employee::query()
            ->with(self::EAGER)
            ->whereIn('status', [EmploymentStatus::Active->value, EmploymentStatus::OnLeave->value])
            ->whereDate('date_of_joining', '<=', $date)
            ->where(function ($query) use ($date): void {
                $query->whereNull('date_of_exit')->orWhereDate('date_of_exit', '>=', $date);
            })
            ->orderBy('employee_code')
            ->get();
    }

    public function selectable(): Collection
    {
        return Employee::query()
            ->with('shift:id,code,name')
            ->where('status', EmploymentStatus::Active->value)
            ->orderBy('employee_code')
            ->get(['id', 'employee_code', 'full_name', 'shift_id', 'department']);
    }
}
