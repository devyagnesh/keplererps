<?php

namespace App\Services;

use App\Models\FinancialYear;
use App\Models\SystemSetting;
use App\Repositories\Interfaces\FinancialYearRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Financial year management (M16).
 */
class FinancialYearService
{
    public function __construct(protected FinancialYearRepositoryInterface $repository) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): FinancialYear
    {
        return $this->repository->findById($id);
    }

    public function current(): ?FinancialYear
    {
        return $this->repository->current();
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): FinancialYear
    {
        return DB::transaction(function () use ($data): FinancialYear {
            $this->assertNoOverlap($data['starts_on'], $data['ends_on']);

            if (! empty($data['is_current'])) {
                FinancialYear::query()->where('is_current', true)->update(['is_current' => false]);
            }

            return $this->repository->create($data);
        });
    }

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): FinancialYear
    {
        return DB::transaction(function () use ($id, $data): FinancialYear {
            $fy = $this->repository->findById($id);
            if ($fy->is_closed) {
                throw ValidationException::withMessages([
                    'financial_year' => 'A closed financial year cannot be edited.',
                ]);
            }

            $this->assertNoOverlap($data['starts_on'], $data['ends_on'], $id);

            if (! empty($data['is_current'])) {
                FinancialYear::query()->where('is_current', true)->where('id', '!=', $id)->update(['is_current' => false]);
            }

            return $this->repository->update($id, $data);
        });
    }

    public function delete(int $id): bool
    {
        $fy = $this->repository->findById($id);
        if ($fy->is_closed || $fy->is_current) {
            throw ValidationException::withMessages([
                'financial_year' => 'Current or closed financial years cannot be deleted.',
            ]);
        }

        return $this->repository->delete($id);
    }

    public function setCurrent(int $id): FinancialYear
    {
        return DB::transaction(function () use ($id): FinancialYear {
            $fy = $this->repository->findById($id);
            if ($fy->is_closed) {
                throw ValidationException::withMessages([
                    'financial_year' => 'A closed financial year cannot be set as current.',
                ]);
            }

            FinancialYear::query()->where('is_current', true)->update(['is_current' => false]);
            $fy->forceFill(['is_current' => true])->save();

            return $fy->fresh();
        });
    }

    public function close(int $id): FinancialYear
    {
        return DB::transaction(function () use ($id): FinancialYear {
            $fy = $this->repository->findById($id);
            if ($fy->is_closed) {
                throw ValidationException::withMessages([
                    'financial_year' => 'This financial year is already closed.',
                ]);
            }

            $fy->forceFill([
                'is_closed' => true,
                'is_current' => false,
                'closed_at' => now(),
                'closed_by' => Auth::id(),
            ])->save();

            // Lock costing method after first FY close (SRS).
            SystemSetting::query()
                ->where('setting_key', 'costing_method')
                ->update(['is_locked' => true]);

            return $fy->fresh();
        });
    }

    protected function assertNoOverlap(string $startsOn, string $endsOn, ?int $ignoreId = null): void
    {
        if ($startsOn > $endsOn) {
            throw ValidationException::withMessages([
                'ends_on' => 'End date must be on or after the start date.',
            ]);
        }

        $overlap = FinancialYear::query()
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where(function ($q) use ($startsOn, $endsOn): void {
                $q->whereBetween('starts_on', [$startsOn, $endsOn])
                    ->orWhereBetween('ends_on', [$startsOn, $endsOn])
                    ->orWhere(function ($inner) use ($startsOn, $endsOn): void {
                        $inner->where('starts_on', '<=', $startsOn)->where('ends_on', '>=', $endsOn);
                    });
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'starts_on' => 'This date range overlaps an existing financial year.',
            ]);
        }
    }
}
