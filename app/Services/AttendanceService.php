<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\SalaryRunStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\SalaryRun;
use App\Models\Shift;
use App\Repositories\Interfaces\EmployeeRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Manual daily attendance marking (M14).
 *
 * Attendance is captured one day at a time for every employee on the rolls; a saved day
 * replaces the previous marking so the sheet can be corrected until payroll is posted.
 */
class AttendanceService
{
    public function __construct(
        protected EmployeeRepositoryInterface $employees,
        protected StatutoryPayrollService $statutory
    ) {}

    /**
     * Attendance sheet for a date: every payable employee with their current marking.
     *
     * @return array{date: string, rows: list<array<string, mixed>>, locked: bool}
     */
    public function sheet(string $date): array
    {
        $employees = $this->employees->payableOn($date);
        $existing = AttendanceRecord::query()
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('employee_id');

        $rows = $employees->map(function (Employee $employee) use ($existing): array {
            /** @var AttendanceRecord|null $record */
            $record = $existing->get($employee->id);

            return [
                'employee_id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'full_name' => $employee->full_name,
                'department' => $employee->department,
                'shift_id' => $record?->shift_id ?? $employee->shift_id,
                'status' => ($record?->status ?? AttendanceStatus::Present)->value,
                'worked_hours' => (float) ($record?->worked_hours ?? 0),
                'overtime_hours' => (float) ($record?->overtime_hours ?? 0),
                'remarks' => $record?->remarks,
                'is_marked' => $record !== null,
            ];
        })->values()->all();

        return [
            'date' => $date,
            'rows' => $rows,
            'locked' => $this->isLocked($date),
        ];
    }

    /**
     * Save the whole sheet for a date.
     *
     * @param  array<string, mixed>  $data
     * @return int Number of employees marked.
     */
    public function save(array $data): int
    {
        $date = (string) $data['attendance_date'];

        if ($this->isLocked($date)) {
            throw ValidationException::withMessages([
                'attendance_date' => 'Payroll for this period has been posted; attendance can no longer be changed.',
            ]);
        }

        $payable = $this->employees->payableOn($date)->keyBy('id');

        return DB::transaction(function () use ($data, $date, $payable): int {
            $marked = 0;

            foreach ($data['rows'] as $row) {
                $employeeId = (int) ($row['employee_id'] ?? 0);
                $employee = $payable->get($employeeId);

                if ($employee === null) {
                    throw ValidationException::withMessages([
                        'rows' => 'One of the employees was not on the rolls on '.$date.'.',
                    ]);
                }

                $record = AttendanceRecord::query()
                    ->where('employee_id', $employeeId)
                    ->whereDate('attendance_date', $date)
                    ->first() ?? new AttendanceRecord;

                $shiftId = $row['shift_id'] ?? $employee->shift_id;
                $workedHours = round((float) ($row['worked_hours'] ?? 0), 2);
                $overtimeHours = array_key_exists('overtime_hours', $row)
                    ? round((float) $row['overtime_hours'], 2)
                    : $this->autoOvertime($employee, $workedHours, $shiftId !== null ? (int) $shiftId : null);

                $record->fill([
                    'employee_id' => $employeeId,
                    'attendance_date' => $date,
                    'shift_id' => $shiftId,
                    'status' => (string) $row['status'],
                    'worked_hours' => $workedHours,
                    'overtime_hours' => $overtimeHours,
                    'remarks' => $row['remarks'] ?? null,
                    'created_by' => Auth::id(),
                ])->save();

                $marked++;
            }

            return $marked;
        });
    }

    /**
     * Attendance summary per employee for a period, used by the salary run.
     *
     * @return Collection<int, array{payable_days: float, worked_days: float, overtime_hours: float, marked_days: int}>
     */
    public function summaryForPeriod(string $fromDate, string $toDate): Collection
    {
        return AttendanceRecord::query()
            ->whereDate('attendance_date', '>=', $fromDate)
            ->whereDate('attendance_date', '<=', $toDate)
            ->get(['employee_id', 'status', 'overtime_hours'])
            ->groupBy('employee_id')
            ->map(fn (Collection $records): array => [
                'payable_days' => round((float) $records->sum(
                    fn (AttendanceRecord $record): float => $record->status->payableFraction()
                ), 2),
                'worked_days' => round((float) $records->sum(
                    fn (AttendanceRecord $record): float => $record->status->isWorked() ? $record->status->payableFraction() : 0.0
                ), 2),
                'overtime_hours' => round((float) $records->sum(
                    fn (AttendanceRecord $record): float => (float) $record->overtime_hours
                ), 2),
                'marked_days' => $records->count(),
            ]);
    }

    /**
     * Record a mobile geo punch (check-in or check-out).
     *
     * @param  array{latitude: float|int|string, longitude: float|int|string, punch_out?: bool}  $data
     */
    public function mobilePunch(int $employeeId, array $data): AttendanceRecord
    {
        $employee = Employee::query()->findOrFail($employeeId);
        $date = now()->toDateString();

        if ($this->isLocked($date)) {
            throw ValidationException::withMessages([
                'attendance_date' => 'Payroll for this period has been posted; attendance can no longer be changed.',
            ]);
        }

        $isPunchOut = (bool) ($data['punch_out'] ?? false);

        return DB::transaction(function () use ($employee, $employeeId, $date, $data, $isPunchOut): AttendanceRecord {
            $record = AttendanceRecord::query()
                ->where('employee_id', $employeeId)
                ->whereDate('attendance_date', $date)
                ->first() ?? new AttendanceRecord;

            if (! $isPunchOut) {
                if ($record->exists && $record->punch_in_at !== null && $record->punch_out_at === null) {
                    throw ValidationException::withMessages([
                        'punch' => 'You are already punched in for today.',
                    ]);
                }

                $record->fill([
                    'employee_id' => $employeeId,
                    'attendance_date' => $date,
                    'shift_id' => $employee->shift_id,
                    'status' => AttendanceStatus::Present->value,
                    'source' => 'mobile',
                    'punch_in_at' => now(),
                    'punch_out_at' => null,
                    'latitude' => round((float) $data['latitude'], 7),
                    'longitude' => round((float) $data['longitude'], 7),
                    'worked_hours' => 0,
                    'overtime_hours' => 0,
                    'created_by' => Auth::id(),
                ])->save();

                return $record->fresh(['employee', 'shift']);
            }

            if (! $record->exists || $record->punch_in_at === null) {
                throw ValidationException::withMessages([
                    'punch' => 'Punch in before punching out.',
                ]);
            }

            if ($record->punch_out_at !== null) {
                throw ValidationException::withMessages([
                    'punch' => 'You have already punched out for today.',
                ]);
            }

            $punchOut = now();
            $workedHours = round($record->punch_in_at->diffInMinutes($punchOut) / 60, 2);
            $overtimeHours = $this->autoOvertime(
                $employee,
                $workedHours,
                $employee->shift_id !== null ? (int) $employee->shift_id : null
            );

            $record->fill([
                'source' => 'mobile',
                'punch_out_at' => $punchOut,
                'latitude' => round((float) $data['latitude'], 7),
                'longitude' => round((float) $data['longitude'], 7),
                'worked_hours' => $workedHours,
                'overtime_hours' => $overtimeHours,
            ])->save();

            return $record->fresh(['employee', 'shift']);
        });
    }

    /**
     * A date is locked once the payroll run covering it has been posted.
     */
    protected function isLocked(string $date): bool
    {
        $day = Carbon::parse($date);

        return SalaryRun::query()
            ->where('status', SalaryRunStatus::Posted->value)
            ->where('period_year', $day->year)
            ->where('period_month', $day->month)
            ->exists();
    }

    /**
     * Import attendance punches from a biometric device CSV.
     *
     * Expected headers: biometric_code, attendance_date, status, worked_hours, overtime_hours
     * Status must be an AttendanceStatus value; missing status defaults to present when hours > 0.
     *
     * @return array{imported: int, skipped: int, errors: list<string>}
     */
    public function importBiometricCsv(string $absolutePath): array
    {
        $handle = fopen($absolutePath, 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages([
                'file' => 'Could not read the uploaded CSV file.',
            ]);
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            throw ValidationException::withMessages(['file' => 'The CSV file is empty.']);
        }

        $map = [];
        foreach ($header as $index => $column) {
            $map[strtolower(trim((string) $column))] = $index;
        }

        if (! isset($map['biometric_code'], $map['attendance_date'])) {
            fclose($handle);
            throw ValidationException::withMessages([
                'file' => 'CSV must include biometric_code and attendance_date columns.',
            ]);
        }

        $employees = Employee::query()
            ->whereNotNull('biometric_code')
            ->get(['id', 'biometric_code', 'shift_id'])
            ->keyBy(fn (Employee $employee): string => (string) $employee->biometric_code);

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($this->csvRowIsBlank($row)) {
                continue;
            }

            $code = trim((string) ($row[$map['biometric_code']] ?? ''));
            $date = trim((string) ($row[$map['attendance_date']] ?? ''));

            if ($code === '' || $date === '') {
                $skipped++;
                $errors[] = "Row {$rowNumber}: biometric_code and attendance_date are required.";
                continue;
            }

            try {
                $parsedDate = Carbon::parse($date)->toDateString();
            } catch (\Throwable) {
                $skipped++;
                $errors[] = "Row {$rowNumber}: invalid attendance_date.";
                continue;
            }

            if (Carbon::parse($parsedDate)->isFuture()) {
                $skipped++;
                $errors[] = "Row {$rowNumber}: future dates are not allowed.";
                continue;
            }

            if ($this->isLocked($parsedDate)) {
                $skipped++;
                $errors[] = "Row {$rowNumber}: payroll for {$parsedDate} is posted.";
                continue;
            }

            $employee = $employees->get($code);
            if ($employee === null) {
                $skipped++;
                $errors[] = "Row {$rowNumber}: unknown biometric_code {$code}.";
                continue;
            }

            $statusRaw = isset($map['status']) ? strtolower(trim((string) ($row[$map['status']] ?? ''))) : '';
            $worked = isset($map['worked_hours']) ? (float) ($row[$map['worked_hours']] ?? 0) : 0.0;
            $overtime = isset($map['overtime_hours']) ? (float) ($row[$map['overtime_hours']] ?? 0) : 0.0;

            $status = AttendanceStatus::tryFrom($statusRaw)
                ?? ($worked > 0 ? AttendanceStatus::Present : AttendanceStatus::Absent);

            $this->save([
                'attendance_date' => $parsedDate,
                'rows' => [[
                    'employee_id' => $employee->id,
                    'shift_id' => $employee->shift_id,
                    'status' => $status->value,
                    'worked_hours' => $worked,
                    'overtime_hours' => $overtime,
                    'remarks' => 'Imported from biometric CSV',
                ]],
            ]);

            $imported++;
        }

        fclose($handle);

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => array_slice($errors, 0, 25),
        ];
    }

    /**
     * Derive overtime from worked hours when the sheet omits an explicit OT value.
     */
    protected function autoOvertime(Employee $employee, float $workedHours, ?int $shiftId): float
    {
        $shift = $shiftId !== null
            ? Shift::query()->find($shiftId)
            : $employee->shift;

        return $this->statutory->computeOvertimeHours($workedHours, $shift);
    }

    /**
     * @param  list<string|null>|false  $row
     */
    protected function csvRowIsBlank(array|false $row): bool
    {
        if ($row === false) {
            return true;
        }

        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
