<?php

namespace App\Repositories\Eloquent;

use App\Models\FinancialYear;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\FinancialYearRepositoryInterface;

/**
 * Eloquent financial year repository.
 */
class FinancialYearRepository implements FinancialYearRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): FinancialYear
    {
        return FinancialYear::query()->findOrFail($id);
    }

    public function current(): ?FinancialYear
    {
        return FinancialYear::query()->where('is_current', true)->first();
    }

    public function create(array $data): FinancialYear
    {
        return FinancialYear::query()->create($data);
    }

    public function update(int $id, array $data): FinancialYear
    {
        $record = $this->findById($id);
        $record->update($data);

        return $record->fresh();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->findById($id)->delete();
    }

    public function getForDataTable(array $params): array
    {
        return $this->buildDataTable(
            FinancialYear::query(),
            ['id', 'code', 'name', 'starts_on', 'ends_on', 'is_current', 'is_closed', 'created_at'],
            ['code', 'name'],
            function (FinancialYear $fy): array {
                return [
                    'id' => $fy->id,
                    'code' => $fy->code,
                    'name' => e($fy->name),
                    'starts_on' => $fy->starts_on?->format('Y-m-d'),
                    'ends_on' => $fy->ends_on?->format('Y-m-d'),
                    'is_current' => $fy->is_current
                        ? '<span class="badge bg-success-transparent">Current</span>'
                        : '—',
                    'is_closed' => $fy->is_closed
                        ? '<span class="badge bg-danger-transparent">Closed</span>'
                        : '<span class="badge bg-primary-transparent">Open</span>',
                    'action' => view('admin.financial-years.partials.actions', ['financialYear' => $fy])->render(),
                ];
            },
            $params
        );
    }
}
