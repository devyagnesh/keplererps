<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveBalance;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Holiday calendar and leave balances (M14 thin).
 */
class HolidayService
{
    /**
     * @return Collection<int, Holiday>
     */
    public function holidays(?int $year = null): Collection
    {
        $year ??= (int) now()->year;

        return Holiday::query()
            ->whereYear('holiday_date', $year)
            ->orderBy('holiday_date')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createHoliday(array $data): Holiday
    {
        return Holiday::query()->updateOrCreate(
            ['holiday_date' => $data['holiday_date']],
            [
                'name' => $data['name'],
                'is_optional' => (bool) ($data['is_optional'] ?? false),
            ]
        );
    }

    public function deleteHoliday(int $id): bool
    {
        return (bool) Holiday::query()->findOrFail($id)->delete();
    }

    /**
     * @return Collection<int, LeaveBalance>
     */
    public function leaveBalances(int $year): Collection
    {
        return LeaveBalance::query()
            ->with('employee:id,employee_code,full_name')
            ->where('year', $year)
            ->orderBy('employee_id')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertLeaveBalance(array $data): LeaveBalance
    {
        Employee::query()->findOrFail((int) $data['employee_id']);

        return LeaveBalance::query()->updateOrCreate(
            [
                'employee_id' => (int) $data['employee_id'],
                'year' => (int) $data['year'],
                'leave_type' => (string) ($data['leave_type'] ?? 'paid'),
            ],
            [
                'opening_days' => round((float) ($data['opening_days'] ?? 0), 2),
                'availed_days' => round((float) ($data['availed_days'] ?? 0), 2),
            ]
        );
    }

    public function remainingDays(LeaveBalance $balance): float
    {
        return round((float) $balance->opening_days - (float) $balance->availed_days, 2);
    }

    public function assertNotHoliday(string $date): void
    {
        if (Holiday::query()->whereDate('holiday_date', $date)->where('is_optional', false)->exists()) {
            throw ValidationException::withMessages([
                'date' => 'Selected date is a company holiday.',
            ]);
        }
    }
}
