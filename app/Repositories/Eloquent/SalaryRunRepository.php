<?php

namespace App\Repositories\Eloquent;

use App\Enums\SalaryRunStatus;
use App\Models\SalaryRun;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\SalaryRunRepositoryInterface;

/**
 * Eloquent payroll run repository.
 */
class SalaryRunRepository implements SalaryRunRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): SalaryRun
    {
        return SalaryRun::query()
            ->with(['slips.employee:id,employee_code,full_name,department,designation', 'postedBy:id,name'])
            ->findOrFail($id);
    }

    public function create(array $data): SalaryRun
    {
        return SalaryRun::query()->create($data);
    }

    public function update(int $id, array $data): SalaryRun
    {
        $run = SalaryRun::query()->findOrFail($id);
        $run->update($data);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        return (bool) SalaryRun::query()->findOrFail($id)->delete();
    }

    public function getForDataTable(array $params): array
    {
        $query = SalaryRun::query();

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }
        if (! empty($params['period_year'])) {
            $query->where('period_year', (int) $params['period_year']);
        }

        return $this->buildDataTable(
            $query,
            ['id', 'document_no', 'period_year', 'payment_date', 'net_total', 'status'],
            ['document_no', 'remarks'],
            fn (SalaryRun $run): array => [
                'id' => $run->id,
                'document_no' => e($run->document_no),
                'period' => e($run->periodLabel()),
                'payment_date' => $run->payment_date?->format('Y-m-d') ?? '—',
                'employee_count' => (int) $run->employee_count,
                'gross_total' => number_format((float) $run->gross_total, 2),
                'deduction_total' => number_format((float) $run->deduction_total, 2),
                'net_total' => number_format((float) $run->net_total, 2),
                'status' => '<span class="badge '.$run->status->badgeClass().'">'.$run->status->label().'</span>',
                'action' => view('admin.salary-runs.partials.actions', ['run' => $run])->render(),
            ],
            $params
        );
    }

    public function findOpenForPeriod(int $year, int $month, ?int $ignoreId = null): ?SalaryRun
    {
        return SalaryRun::query()
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->where('status', '!=', SalaryRunStatus::Cancelled->value)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->first();
    }
}
