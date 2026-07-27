<?php

namespace Tests\Feature\Admin;

use App\Enums\AttendanceStatus;
use App\Enums\LedgerAccountType;
use App\Enums\NotificationChannel;
use App\Enums\NotificationEvent;
use App\Enums\NotificationRecipientType;
use App\Models\Employee;
use App\Models\LedgerAccount;
use App\Models\NotificationRule;
use App\Models\User;
use App\Notifications\CatalogueNotification;
use App\Services\FinanceReportService;
use App\Services\NotificationDispatchService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Coverage for the half-built module depth work (email, HR import, P&L/BS, activity log).
 */
class IncompleteModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_notification_channel_is_supported_and_dispatches_mail(): void
    {
        Notification::fake();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $user = User::factory()->superAdmin()->create(['email' => 'ops@example.com']);

        NotificationRule::factory()->create([
            'event' => NotificationEvent::SalaryRunPosted,
            'channel' => NotificationChannel::Email,
            'recipient_type' => NotificationRecipientType::Role,
            'recipient_value' => 'super-admin',
            'subject_template' => 'Salary {{document_no}}',
            'body_template' => 'Period {{period}}',
            'is_active' => true,
        ]);

        $sent = app(NotificationDispatchService::class)->dispatch(
            NotificationEvent::SalaryRunPosted,
            ['document_no' => 'PAY-9', 'period' => 'Jul 2026', 'net_total' => '1']
        );

        $this->assertSame(1, $sent);
        $this->assertTrue(NotificationChannel::Email->isSupported());
        $this->assertTrue(NotificationChannel::WhatsApp->isSupported());

        Notification::assertSentTo(
            $user,
            CatalogueNotification::class,
            fn (CatalogueNotification $notification): bool => $notification->rule->channel === NotificationChannel::Email
        );
    }

    public function test_biometric_csv_imports_attendance_by_device_code(): void
    {
        $user = User::factory()->superAdmin()->create();
        $employee = Employee::factory()->create(['biometric_code' => 'DEV-42']);
        $date = now()->subDay()->toDateString();

        $csv = "biometric_code,attendance_date,status,worked_hours,overtime_hours\nDEV-42,{$date},present,8,1\n";
        $file = UploadedFile::fake()->createWithContent('punches.csv', $csv);

        $this->actingAs($user)
            ->postJson(route('admin.attendance.import'), ['file' => $file])
            ->assertOk()
            ->assertJsonPath('data.imported', 1);

        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $employee->id,
            'status' => AttendanceStatus::Present->value,
        ]);
    }

    public function test_profit_and_loss_and_balance_sheet_render(): void
    {
        $user = User::factory()->superAdmin()->create();

        LedgerAccount::query()->create([
            'code' => '4100X',
            'name' => 'Sales',
            'account_type' => LedgerAccountType::Income,
            'is_active' => true,
            'opening_balance' => 0,
            'opening_balance_side' => 'credit',
        ]);
        LedgerAccount::query()->create([
            'code' => '5100X',
            'name' => 'Purchases',
            'account_type' => LedgerAccountType::Expense,
            'is_active' => true,
            'opening_balance' => 0,
            'opening_balance_side' => 'debit',
        ]);
        LedgerAccount::query()->create([
            'code' => '1000X',
            'name' => 'Cash',
            'account_type' => LedgerAccountType::Asset,
            'is_active' => true,
            'opening_balance' => 1000,
            'opening_balance_side' => 'debit',
        ]);

        $this->actingAs($user)->get(route('admin.finance-reports.profit-and-loss'))->assertOk();
        $this->actingAs($user)->get(route('admin.finance-reports.balance-sheet'))->assertOk();

        $pnl = app(FinanceReportService::class)->profitAndLoss(now()->startOfMonth()->toDateString(), now()->toDateString());
        $this->assertArrayHasKey('net_profit', $pnl);

        $bs = app(FinanceReportService::class)->balanceSheet(now()->toDateString());
        $this->assertGreaterThanOrEqual(1000, $bs['total_assets']);
    }

    public function test_activity_log_viewer_renders(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->get(route('admin.activity-logs.index'))
            ->assertOk()
            ->assertSee('Activity Log');
    }

    public function test_employee_accepts_statutory_fields(): void
    {
        $user = User::factory()->superAdmin()->create();

        $response = $this->actingAs($user)->postJson(route('admin.employees.store'), [
            'full_name' => 'Ravi Kumar',
            'date_of_joining' => now()->subYear()->toDateString(),
            'status' => 'active',
            'monthly_gross' => 25000,
            'basic_percent' => 50,
            'uan' => '100200300400',
            'pf_number' => 'PF/MH/123',
            'esi_number' => 'ESI-99',
            'aadhaar_last4' => '4321',
            'biometric_code' => 'BIO-1',
        ])->assertCreated();

        $this->assertDatabaseHas('employees', [
            'id' => $response->json('data.id'),
            'uan' => '100200300400',
            'biometric_code' => 'BIO-1',
        ]);
    }
}
