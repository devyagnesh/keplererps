<?php

namespace Tests\Feature\Admin;

use App\Enums\DocumentStatus;
use App\Enums\VoucherType;
use App\Models\Item;
use App\Models\ItemWarehouseSetting;
use App\Models\JournalVoucher;
use App\Models\JournalVoucherLine;
use App\Models\LedgerAccount;
use App\Models\Party;
use App\Models\PurchaseIndent;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\DocumentPdfService;
use App\Services\RecycleBinService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * SRS completeness sprint coverage.
 */
class SrsCompletenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_indent_can_be_created_from_suggestions_and_approved(): void
    {
        $this->seed(SystemSettingSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->superAdmin()->create();
        $warehouse = Warehouse::factory()->create(['is_leaf' => true, 'is_active' => true]);
        $item = Item::factory()->create(['is_purchasable' => true, 'is_active' => true]);
        ItemWarehouseSetting::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'reorder_level' => 100,
            'reorder_qty' => 50,
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.purchase-indents.store'), [
                'warehouse_id' => $warehouse->id,
            ])
            ->assertCreated();

        $indent = PurchaseIndent::query()->first();
        $this->assertNotNull($indent);

        $this->actingAs($admin)
            ->postJson(route('admin.purchase-indents.approve', $indent))
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_bank_line_can_be_reconciled(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->superAdmin()->create();
        $account = LedgerAccount::query()->create([
            'code' => '1101',
            'name' => 'Test Bank',
            'account_type' => 'asset',
            'account_group' => 'Cash & Bank',
            'is_active' => true,
            'opening_balance' => 0,
            'opening_balance_side' => 'debit',
        ]);
        $voucher = JournalVoucher::query()->create([
            'document_no' => 'JV-TEST-1',
            'document_date' => now()->toDateString(),
            'voucher_type' => VoucherType::Payment,
            'status' => DocumentStatus::Posted,
            'total_debit' => 100,
            'total_credit' => 100,
        ]);
        $line = JournalVoucherLine::query()->create([
            'journal_voucher_id' => $voucher->id,
            'ledger_account_id' => $account->id,
            'debit' => 0,
            'credit' => 100,
            'sort_order' => 0,
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.bank-reconciliation.reconcile'), [
                'line_ids' => [$line->id],
                'bank_date' => now()->toDateString(),
            ])
            ->assertOk();

        $this->assertNotNull($line->fresh()->reconciled_at);
    }

    public function test_holiday_and_locale_and_pdf_and_recycle_bin(): void
    {
        $this->seed(SystemSettingSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->postJson(route('admin.holidays.store'), [
                'holiday_date' => now()->addDays(10)->toDateString(),
                'name' => 'Diwali',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('holidays', ['name' => 'Diwali']);

        $this->actingAs($admin)
            ->post(route('admin.locale.update'), ['locale' => 'hi'])
            ->assertRedirect();
        $this->assertSame('hi', session('ui_locale'));

        $pdf = app(DocumentPdfService::class)->fromHtml('<html><body>Test</body></html>');
        $this->assertStringContainsString('%PDF', substr($pdf, 0, 8));

        $party = Party::factory()->create();
        $party->delete();
        $rows = app(RecycleBinService::class)->list('party');
        $this->assertNotEmpty($rows);
        app(RecycleBinService::class)->restore('party', $party->id);
        $this->assertNull($party->fresh()->deleted_at);
    }

    public function test_gstr2b_and_lead_import(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->superAdmin()->create();

        $csv = "gstin,invoice_no,invoice_date,taxable_value,igst,cgst,sgst\n24AAAAA0000A1Z5,BILL-1,2026-07-01,1000,0,90,90\n";
        $file = UploadedFile::fake()->createWithContent('gstr2b.csv', $csv);

        $this->actingAs($admin)
            ->postJson(route('admin.gstr2b.store'), [
                'period' => now()->format('Y-m'),
                'file' => $file,
            ])
            ->assertCreated();

        $leadCsv = "company_name,contact_person,mobile,email,source\nAcme Pipes,Ravi,9876501234,ravi@example.com,indiamart\n";
        $leadFile = UploadedFile::fake()->createWithContent('leads.csv', $leadCsv);

        $this->actingAs($admin)
            ->postJson(route('admin.leads.import'), ['file' => $leadFile])
            ->assertOk()
            ->assertJsonPath('data.imported', 1);
    }

    public function test_backup_creates_archive_log(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->postJson(route('admin.backups.store'))
            ->assertCreated()
            ->assertJsonPath('data.status', 'ready');

        $this->assertDatabaseHas('backup_logs', ['status' => 'ready']);
    }
}
