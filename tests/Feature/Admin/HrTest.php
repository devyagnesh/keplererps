<?php

namespace Tests\Feature\Admin;

use App\Enums\AttendanceStatus;
use App\Enums\DocumentStatus;
use App\Enums\EmploymentStatus;
use App\Enums\SalaryRunStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\JournalVoucher;
use App\Models\SalaryRun;
use App\Models\Shift;
use App\Models\User;
use App\Services\AttendanceService;
use Database\Seeders\LedgerAccountSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Feature tests for M14 employees, attendance and payroll.
 */
class HrTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_is_created_with_a_generated_code_and_derived_pay_split(): void
    {
        $user = User::factory()->superAdmin()->create();
        $shift = Shift::factory()->create();

        $response = $this->actingAs($user)->postJson(route('admin.employees.store'), [
            'full_name' => 'Asha Patel',
            'designation' => 'Machine Operator',
            'department' => 'Production',
            'shift_id' => $shift->id,
            'date_of_joining' => now()->subYear()->toDateString(),
            'status' => EmploymentStatus::Active->value,
            'monthly_gross' => 30000,
            'basic_percent' => 60,
            'overtime_rate_per_hour' => 100,
        ])->assertCreated();

        $employee = Employee::query()->findOrFail($response->json('data.id'));

        $this->assertNotEmpty($employee->employee_code);
        $this->assertSame(18000.0, $employee->basicAmount());
        $this->assertSame(12000.0, $employee->allowanceAmount());
    }

    public function test_exit_date_is_required_when_an_employee_is_marked_resigned(): void
    {
        $user = User::factory()->superAdmin()->create();
        $employee = Employee::factory()->create();

        $this->actingAs($user)->putJson(route('admin.employees.update', $employee), [
            'full_name' => $employee->full_name,
            'date_of_joining' => $employee->date_of_joining->toDateString(),
            'status' => EmploymentStatus::Resigned->value,
            'monthly_gross' => 30000,
            'basic_percent' => 50,
        ])->assertStatus(422);

        $this->actingAs($user)->putJson(route('admin.employees.update', $employee), [
            'full_name' => $employee->full_name,
            'date_of_joining' => $employee->date_of_joining->toDateString(),
            'date_of_exit' => now()->toDateString(),
            'status' => EmploymentStatus::Resigned->value,
            'monthly_gross' => 30000,
            'basic_percent' => 50,
        ])->assertOk();
    }

    public function test_employee_with_attendance_cannot_be_deleted(): void
    {
        $user = User::factory()->superAdmin()->create();
        $employee = Employee::factory()->create();
        AttendanceRecord::factory()->create(['employee_id' => $employee->id]);

        $this->actingAs($user)
            ->deleteJson(route('admin.employees.destroy', $employee))
            ->assertStatus(422);
    }

    public function test_attendance_sheet_saves_one_row_per_employee_and_is_idempotent(): void
    {
        $user = User::factory()->superAdmin()->create();
        $employee = Employee::factory()->create();
        $date = now()->subDay()->toDateString();

        $payload = [
            'attendance_date' => $date,
            'rows' => [[
                'employee_id' => $employee->id,
                'status' => AttendanceStatus::HalfDay->value,
                'worked_hours' => 4,
                'overtime_hours' => 0,
            ]],
        ];

        $this->actingAs($user)->postJson(route('admin.attendance.store'), $payload)->assertOk();
        $this->actingAs($user)->postJson(route('admin.attendance.store'), $payload)->assertOk();

        $this->assertSame(1, AttendanceRecord::query()->where('employee_id', $employee->id)->count());
        $this->assertSame(
            AttendanceStatus::HalfDay,
            AttendanceRecord::query()->where('employee_id', $employee->id)->firstOrFail()->status
        );

        $this->actingAs($user)->get(route('admin.attendance.index', ['attendance_date' => $date]))->assertOk();
    }

    public function test_attendance_cannot_be_marked_for_a_future_date(): void
    {
        $user = User::factory()->superAdmin()->create();
        $employee = Employee::factory()->create();

        $this->actingAs($user)->postJson(route('admin.attendance.store'), [
            'attendance_date' => now()->addDay()->toDateString(),
            'rows' => [['employee_id' => $employee->id, 'status' => AttendanceStatus::Present->value]],
        ])->assertStatus(422);
    }

    public function test_salary_run_prorates_on_attendance_and_posts_a_balanced_journal(): void
    {
        $this->seed(SystemSettingSeeder::class);
        $this->seed(LedgerAccountSeeder::class);

        $user = User::factory()->superAdmin()->create();
        $period = now()->subMonthNoOverflow()->startOfMonth();
        $days = $period->daysInMonth;

        $employee = Employee::factory()->create([
            'monthly_gross' => 30000,
            'basic_percent' => 50,
            'fixed_deduction' => 1000,
            'overtime_rate_per_hour' => 100,
            'date_of_joining' => $period->copy()->subMonth()->toDateString(),
        ]);

        // Half the month present, the rest unpaid leave, plus 5 overtime hours.
        for ($day = 1; $day <= $days; $day++) {
            $isPaid = $day <= (int) floor($days / 2);
            AttendanceRecord::factory()->create([
                'employee_id' => $employee->id,
                'attendance_date' => $period->copy()->addDays($day - 1)->toDateString(),
                'status' => $isPaid ? AttendanceStatus::Present : AttendanceStatus::UnpaidLeave,
                'worked_hours' => $isPaid ? 8 : 0,
                'overtime_hours' => $day === 1 ? 5 : 0,
            ]);
        }

        $runId = $this->actingAs($user)->postJson(route('admin.salary-runs.store'), [
            'period_year' => $period->year,
            'period_month' => $period->month,
            'payment_date' => $period->copy()->endOfMonth()->toDateString(),
        ])->assertCreated()->json('data.id');

        $slip = SalaryRun::query()->findOrFail($runId)->slips()->firstOrFail();
        $paidDays = (float) floor($days / 2);
        $expectedGross = round((30000 * ($paidDays / $days)) + 500, 2);

        $this->assertEqualsWithDelta($paidDays, (float) $slip->payable_days, 0.01);
        $this->assertEqualsWithDelta($expectedGross, (float) $slip->gross_amount, 0.05);
        $this->assertEqualsWithDelta($expectedGross - 1000, (float) $slip->net_amount, 0.05);

        $this->actingAs($user)->postJson(route('admin.salary-runs.post', $runId))->assertOk();

        $run = SalaryRun::query()->findOrFail($runId);
        $this->assertSame(SalaryRunStatus::Posted, $run->status);

        $voucher = JournalVoucher::query()
            ->where('source_type', SalaryRun::class)
            ->where('source_id', $runId)
            ->with('lines')
            ->firstOrFail();

        $this->assertSame(DocumentStatus::Posted, $voucher->status);
        $this->assertEqualsWithDelta(
            (float) $voucher->lines->sum('debit'),
            (float) $voucher->lines->sum('credit'),
            0.01
        );
        $this->assertEqualsWithDelta(
            (float) $run->net_total,
            (float) $voucher->lines->sum('credit') - (float) $run->deduction_total,
            0.01
        );
    }

    public function test_posted_run_locks_attendance_and_blocks_further_changes(): void
    {
        $user = User::factory()->superAdmin()->create();
        $period = now()->subMonthNoOverflow()->startOfMonth();
        Employee::factory()->create(['date_of_joining' => $period->copy()->subMonth()->toDateString()]);

        $runId = $this->actingAs($user)->postJson(route('admin.salary-runs.store'), [
            'period_year' => $period->year,
            'period_month' => $period->month,
            'payment_date' => $period->copy()->endOfMonth()->toDateString(),
        ])->assertCreated()->json('data.id');

        $this->actingAs($user)->postJson(route('admin.salary-runs.post', $runId))->assertOk();

        // Attendance inside a posted period is frozen.
        $this->actingAs($user)->postJson(route('admin.attendance.store'), [
            'attendance_date' => $period->copy()->addDay()->toDateString(),
            'rows' => [[
                'employee_id' => Employee::query()->value('id'),
                'status' => AttendanceStatus::Present->value,
            ]],
        ])->assertStatus(422);

        // A posted run can neither be recalculated nor deleted.
        $this->actingAs($user)->postJson(route('admin.salary-runs.recalculate', $runId))->assertStatus(422);
        $this->actingAs($user)->deleteJson(route('admin.salary-runs.destroy', $runId))->assertStatus(422);
    }

    public function test_only_one_open_run_per_period_and_cancellation_frees_it(): void
    {
        $user = User::factory()->superAdmin()->create();
        $period = now()->subMonthNoOverflow()->startOfMonth();
        Employee::factory()->create(['date_of_joining' => $period->copy()->subMonth()->toDateString()]);

        $payload = [
            'period_year' => $period->year,
            'period_month' => $period->month,
            'payment_date' => $period->copy()->endOfMonth()->toDateString(),
        ];

        $firstId = $this->actingAs($user)->postJson(route('admin.salary-runs.store'), $payload)->assertCreated()->json('data.id');
        $this->actingAs($user)->postJson(route('admin.salary-runs.store'), $payload)->assertStatus(422);

        $this->actingAs($user)->postJson(route('admin.salary-runs.cancel', $firstId))->assertOk();
        $this->actingAs($user)->postJson(route('admin.salary-runs.store'), $payload)->assertCreated();
    }

    public function test_resigned_employees_are_left_out_of_the_run(): void
    {
        $user = User::factory()->superAdmin()->create();
        $period = now()->subMonthNoOverflow()->startOfMonth();

        Employee::factory()->create(['date_of_joining' => $period->copy()->subMonth()->toDateString()]);
        Employee::factory()->resigned($period->copy()->subDay()->toDateString())->create([
            'date_of_joining' => $period->copy()->subYear()->toDateString(),
        ]);

        $runId = $this->actingAs($user)->postJson(route('admin.salary-runs.store'), [
            'period_year' => $period->year,
            'period_month' => $period->month,
            'payment_date' => $period->copy()->endOfMonth()->toDateString(),
        ])->assertCreated()->json('data.id');

        $this->assertSame(1, SalaryRun::query()->findOrFail($runId)->employee_count);
    }

    public function test_hr_screens_render(): void
    {
        $user = User::factory()->superAdmin()->create();
        $employee = Employee::factory()->create();
        $period = now()->subMonthNoOverflow()->startOfMonth();

        $runId = $this->actingAs($user)->postJson(route('admin.salary-runs.store'), [
            'period_year' => $period->year,
            'period_month' => $period->month,
            'payment_date' => $period->copy()->endOfMonth()->toDateString(),
        ])->assertCreated()->json('data.id');

        $this->actingAs($user)->get(route('admin.employees.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.employees.create'))->assertOk();
        $this->actingAs($user)->get(route('admin.employees.edit', $employee))->assertOk();
        $this->actingAs($user)->get(route('admin.shifts.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.attendance.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.salary-runs.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.salary-runs.create'))->assertOk();
        $this->actingAs($user)->get(route('admin.salary-runs.edit', $runId))->assertOk();
        $this->actingAs($user)
            ->get(route('admin.salary-runs.print', $runId))
            ->assertOk()
            ->assertSee('Payslip');
    }

    public function test_shift_with_employees_cannot_be_deleted(): void
    {
        $user = User::factory()->superAdmin()->create();
        $shift = Shift::factory()->create();
        Employee::factory()->create(['shift_id' => $shift->id]);

        $this->actingAs($user)->deleteJson(route('admin.shifts.destroy', $shift))->assertStatus(422);

        $spare = Shift::factory()->create();
        $this->actingAs($user)->deleteJson(route('admin.shifts.destroy', $spare))->assertOk();
    }

    public function test_shift_duration_excludes_the_break(): void
    {
        $shift = Shift::factory()->create(['start_time' => '09:00', 'end_time' => '18:00', 'break_minutes' => 30]);
        $night = Shift::factory()->create(['start_time' => '22:00', 'end_time' => '06:00', 'break_minutes' => 60]);

        $this->assertSame(8.5, $shift->durationHours());
        $this->assertSame(7.0, $night->durationHours());
    }

    public function test_attendance_summary_weighs_statuses_by_payable_fraction(): void
    {
        $employee = Employee::factory()->create();
        $start = Carbon::create(2026, 3, 1);

        AttendanceRecord::factory()->create(['employee_id' => $employee->id, 'attendance_date' => $start->toDateString(), 'status' => AttendanceStatus::Present]);
        AttendanceRecord::factory()->create(['employee_id' => $employee->id, 'attendance_date' => $start->copy()->addDay()->toDateString(), 'status' => AttendanceStatus::HalfDay]);
        AttendanceRecord::factory()->create(['employee_id' => $employee->id, 'attendance_date' => $start->copy()->addDays(2)->toDateString(), 'status' => AttendanceStatus::Absent]);
        AttendanceRecord::factory()->create(['employee_id' => $employee->id, 'attendance_date' => $start->copy()->addDays(3)->toDateString(), 'status' => AttendanceStatus::PaidLeave]);

        $summary = app(AttendanceService::class)
            ->summaryForPeriod($start->toDateString(), $start->copy()->endOfMonth()->toDateString())
            ->get($employee->id);

        $this->assertEqualsWithDelta(2.5, $summary['payable_days'], 0.01);
        $this->assertEqualsWithDelta(1.5, $summary['worked_days'], 0.01);
        $this->assertSame(4, $summary['marked_days']);
    }
}
