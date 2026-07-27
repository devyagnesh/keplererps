<?php

namespace Tests\Feature\Admin;

use App\Enums\DocumentStatus;
use App\Enums\EmploymentStatus;
use App\Enums\VoucherType;
use App\Models\Employee;
use App\Models\InstallLock;
use App\Models\Item;
use App\Models\ProductionEntry;
use App\Models\SalaryRun;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Enums\WorkOrderStatus;
use App\Models\Bom;
use App\Services\GstGspService;
use App\Services\StatutoryPayrollService;
use App\Services\TallyExportService;
use App\Services\WhatsAppService;
use Database\Seeders\LedgerAccountSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Feature tests for remaining SRS depth gaps (piece payroll, mobile punch, tally export, installer).
 */
class SrsIncompleteDepthTest extends TestCase
{
    use RefreshDatabase;

    public function test_piece_earnings_helper_accepts_rate_override(): void
    {
        $employee = Employee::factory()->create(['piece_rate' => 5.0]);
        $statutory = app(StatutoryPayrollService::class);

        $this->assertSame(50.0, $statutory->pieceEarnings($employee, 10));
        $this->assertSame(120.0, $statutory->pieceEarnings($employee, 10, 12.0));
    }

    public function test_salary_slip_columns_exist_after_migration(): void
    {
        $this->assertTrue(Schema::hasColumn('salary_slips', 'pieces'));
        $this->assertTrue(Schema::hasColumn('salary_slips', 'piece_amount'));
        $this->assertTrue(Schema::hasColumn('employees', 'date_of_birth'));
        $this->assertTrue(Schema::hasColumn('items', 'piece_rate'));
    }

    public function test_salary_run_includes_piece_earnings_from_posted_production(): void
    {
        $this->seed(SystemSettingSeeder::class);
        $this->seed(LedgerAccountSeeder::class);

        $operator = User::factory()->create();
        $employee = Employee::factory()->create([
            'user_id' => $operator->id,
            'piece_rate' => 10,
            'status' => EmploymentStatus::Active,
        ]);

        $item = Item::factory()->create(['piece_rate' => 15]);
        $bom = Bom::factory()->create(['item_id' => $item->id]);
        $warehouse = Warehouse::factory()->create(['is_leaf' => true]);
        $workOrder = WorkOrder::query()->create([
            'document_no' => 'WO-PIECE-1',
            'document_date' => now()->startOfMonth()->toDateString(),
            'item_id' => $item->id,
            'bom_id' => $bom->id,
            'planned_quantity' => 100,
            'planned_start_date' => now()->startOfMonth()->toDateString(),
            'planned_end_date' => now()->endOfMonth()->toDateString(),
            'source_warehouse_id' => $warehouse->id,
            'target_warehouse_id' => $warehouse->id,
            'status' => WorkOrderStatus::Released->value,
        ]);

        ProductionEntry::query()->create([
            'document_no' => 'PE-00001',
            'document_date' => now()->startOfMonth()->toDateString(),
            'work_order_id' => $workOrder->id,
            'good_quantity' => 100,
            'operator_user_id' => $operator->id,
            'posted_at' => now(),
            'posted_by' => $operator->id,
            'created_by' => $operator->id,
        ]);

        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->postJson(route('admin.salary-runs.store'), [
            'period_year' => (int) now()->year,
            'period_month' => (int) now()->month,
            'payment_date' => now()->endOfMonth()->toDateString(),
        ])->assertCreated();

        $run = SalaryRun::query()->with('slips')->first();
        $slip = $run?->slips->firstWhere('employee_id', $employee->id);

        $this->assertNotNull($slip);
        $this->assertSame('100.0000', (string) $slip->pieces);
        $this->assertSame('1500.00', (string) $slip->piece_amount);
    }

    public function test_gst_eway_submit_dry_run(): void
    {
        $result = app(GstGspService::class)->submitEwayBill([
            'document_no' => 'DC-00001',
            'value' => 1000,
        ]);

        $this->assertContains($result['status'], ['queued', 'dry_run']);
        $this->assertArrayHasKey('eway_bill_number', $result);
    }

    public function test_whatsapp_send_template_without_credentials_dry_runs(): void
    {
        $result = app(WhatsAppService::class)->sendTemplate(
            '9876543210',
            'salary_slip',
            ['July 2026', '25000.00']
        );

        $this->assertTrue($result['dry_run']);
        $this->assertContains($result['status'], ['skipped', 'queued_dry_run']);
    }

    public function test_installer_rejects_when_install_key_not_configured(): void
    {
        $this->get(route('install.show'))->assertOk();

        $this->post(route('install.run'), [
            'install_key' => 'wrong-key',
        ])->assertSessionHasErrors('install_key');

        $this->assertFalse(InstallLock::query()->where('is_installed', true)->exists());
    }

    public function test_installer_blocks_when_already_installed(): void
    {
        InstallLock::query()->create([
            'is_installed' => true,
            'installed_at' => now(),
        ]);

        $this->get(route('install.show'))->assertForbidden();
    }

    public function test_tally_export_returns_xml_for_empty_period(): void
    {
        $from = now()->startOfYear()->toDateString();
        $to = now()->endOfYear()->toDateString();

        $xml = app(TallyExportService::class)->exportVouchers($from, $to);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('<ENVELOPE>', $xml);
    }

    public function test_tally_export_includes_posted_journal_voucher(): void
    {
        $account = \App\Models\LedgerAccount::query()->create([
            'code' => '9999',
            'name' => 'Test Ledger',
            'account_type' => 'expense',
            'account_group' => 'Expenses',
            'is_active' => true,
            'opening_balance' => 0,
            'opening_balance_side' => 'debit',
        ]);

        $voucher = \App\Models\JournalVoucher::query()->create([
            'document_no' => 'JV-TALLY-1',
            'document_date' => now()->toDateString(),
            'voucher_type' => VoucherType::Journal,
            'status' => DocumentStatus::Posted,
            'total_debit' => 100,
            'total_credit' => 100,
            'posted_at' => now(),
        ]);

        \App\Models\JournalVoucherLine::query()->create([
            'journal_voucher_id' => $voucher->id,
            'ledger_account_id' => $account->id,
            'debit' => 100,
            'credit' => 0,
            'sort_order' => 1,
        ]);

        \App\Models\JournalVoucherLine::query()->create([
            'journal_voucher_id' => $voucher->id,
            'ledger_account_id' => $account->id,
            'debit' => 0,
            'credit' => 100,
            'sort_order' => 2,
        ]);

        $xml = app(TallyExportService::class)->exportVouchers(
            now()->toDateString(),
            now()->toDateString()
        );

        $this->assertStringContainsString('JV-TALLY-1', $xml);
        $this->assertStringContainsString('Test Ledger', $xml);
    }

    public function test_mobile_punch_records_geo_attendance(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $user = User::factory()->superAdmin()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->postJson(route('admin.attendance.mobile-punch'), [
            'latitude' => 23.0225,
            'longitude' => 72.5714,
        ])->assertOk()
            ->assertJsonPath('data.source', 'mobile');

        $this->actingAs($user)->postJson(route('admin.attendance.mobile-punch'), [
            'latitude' => 23.0225,
            'longitude' => 72.5714,
            'punch_out' => true,
        ])->assertOk()
            ->assertJsonPath('data.employee_id', $employee->id);
    }
}
