<?php

namespace Tests\Feature\Admin;

use App\Enums\DocumentStatus;
use App\Enums\PartyType;
use App\Enums\TrackingType;
use App\Enums\VoucherType;
use App\Models\Item;
use App\Models\JournalVoucher;
use App\Models\LedgerAccount;
use App\Models\Party;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\State;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\FinanceReportService;
use App\Services\GstReportService;
use Database\Seeders\LedgerAccountSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for the accounts / finance / GST module (M13).
 */
class FinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_voucher_must_balance_before_it_can_be_posted(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->seedChartOfAccounts();

        $cash = LedgerAccount::query()->where('code', '1100')->firstOrFail();
        $sales = LedgerAccount::query()->where('code', '4100')->firstOrFail();

        $this->actingAs($user)
            ->postJson(route('admin.journal-vouchers.store'), [
                'document_date' => now()->toDateString(),
                'voucher_type' => VoucherType::Journal->value,
                'narration' => 'Unbalanced entry',
                'lines' => [
                    ['ledger_account_id' => $cash->id, 'debit' => 500, 'credit' => 0],
                    ['ledger_account_id' => $sales->id, 'debit' => 0, 'credit' => 400],
                ],
            ])
            ->assertCreated();

        $voucherId = (int) JournalVoucher::query()->latest('id')->value('id');

        $this->actingAs($user)
            ->postJson(route('admin.journal-vouchers.post', $voucherId))
            ->assertStatus(422)
            ->assertJsonPath('status', false);

        $this->actingAs($user)
            ->putJson(route('admin.journal-vouchers.update', $voucherId), [
                'document_date' => now()->toDateString(),
                'narration' => 'Balanced entry',
                'lines' => [
                    ['ledger_account_id' => $cash->id, 'debit' => 500, 'credit' => 0],
                    ['ledger_account_id' => $sales->id, 'debit' => 0, 'credit' => 500],
                ],
            ])
            ->assertOk();

        $this->actingAs($user)
            ->postJson(route('admin.journal-vouchers.post', $voucherId))
            ->assertOk()
            ->assertJsonPath('data.status', DocumentStatus::Posted->value);
    }

    public function test_posted_voucher_cannot_be_edited_or_deleted(): void
    {
        $user = User::factory()->superAdmin()->create();
        $voucher = $this->postedManualVoucher($user, 250.0);

        $this->actingAs($user)
            ->deleteJson(route('admin.journal-vouchers.destroy', $voucher->id))
            ->assertStatus(422);

        $this->actingAs($user)
            ->postJson(route('admin.journal-vouchers.cancel', $voucher->id))
            ->assertOk()
            ->assertJsonPath('data.status', DocumentStatus::Cancelled->value);
    }

    public function test_system_voucher_type_cannot_be_created_manually(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->seedChartOfAccounts();

        $cash = LedgerAccount::query()->where('code', '1100')->firstOrFail();
        $sales = LedgerAccount::query()->where('code', '4100')->firstOrFail();

        $this->actingAs($user)
            ->postJson(route('admin.journal-vouchers.store'), [
                'document_date' => now()->toDateString(),
                'voucher_type' => VoucherType::Sales->value,
                'lines' => [
                    ['ledger_account_id' => $cash->id, 'debit' => 100, 'credit' => 0],
                    ['ledger_account_id' => $sales->id, 'debit' => 0, 'credit' => 100],
                ],
            ])
            ->assertStatus(422);
    }

    public function test_confirming_a_sales_invoice_auto_posts_a_balanced_sales_voucher(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->seedChartOfAccounts();

        $invoice = $this->confirmedInvoice($user, quantity: 10, rate: 100);

        $voucher = JournalVoucher::query()
            ->with('lines')
            ->where('source_type', SalesInvoice::class)
            ->where('source_id', $invoice->id)
            ->firstOrFail();

        $this->assertSame(VoucherType::Sales, $voucher->voucher_type);
        $this->assertSame(DocumentStatus::Posted, $voucher->status);
        $this->assertEqualsWithDelta((float) $invoice->grand_total, (float) $voucher->total_debit, 0.01);
        $this->assertEqualsWithDelta((float) $voucher->total_debit, (float) $voucher->total_credit, 0.01);

        $receivable = LedgerAccount::query()->where('code', '1200')->firstOrFail();
        $receivableLine = $voucher->lines->firstWhere('ledger_account_id', $receivable->id);

        $this->assertNotNull($receivableLine);
        $this->assertSame($invoice->customer_id, $receivableLine->party_id);
    }

    public function test_ageing_reports_the_outstanding_receivable_for_the_customer(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->seedChartOfAccounts();

        $invoice = $this->confirmedInvoice($user, quantity: 4, rate: 250);
        $ageing = app(FinanceReportService::class)->ageing('receivable');

        $this->assertCount(1, $ageing);
        $this->assertSame($invoice->customer_id, $ageing[0]['party_id']);
        $this->assertEqualsWithDelta((float) $invoice->grand_total, $ageing[0]['outstanding'], 0.01);
        $this->assertEqualsWithDelta((float) $invoice->grand_total, $ageing[0]['bucket_0_30'], 0.01);
    }

    public function test_trial_balance_totals_match_across_debit_and_credit(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->postedManualVoucher($user, 1500.0);

        $report = app(FinanceReportService::class)->trialBalance(
            now()->subMonth()->toDateString(),
            now()->addDay()->toDateString()
        );

        $this->assertNotEmpty($report['rows']);
        $this->assertEqualsWithDelta($report['total_debit'], $report['total_credit'], 0.01);
    }

    public function test_gst_worksheet_includes_the_confirmed_invoice(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->seedChartOfAccounts();

        $invoice = $this->confirmedInvoice($user, quantity: 2, rate: 500);

        $rows = app(GstReportService::class)->outwardSupplies(
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString()
        );

        $this->assertCount(1, $rows);
        $this->assertSame($invoice->document_no, $rows[0]['invoice_no']);
        $this->assertEqualsWithDelta(1000.0, $rows[0]['taxable_value'], 0.01);
    }

    public function test_finance_pages_render(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->seedChartOfAccounts();

        $this->actingAs($user)->get(route('admin.ledger-accounts.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.ledger-accounts.create'))->assertOk();
        $this->actingAs($user)->get(route('admin.journal-vouchers.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.journal-vouchers.create'))->assertOk();
        $this->actingAs($user)->get(route('admin.finance-reports.ageing'))->assertOk();
        $this->actingAs($user)->get(route('admin.finance-reports.statement'))->assertOk();
        $this->actingAs($user)->get(route('admin.finance-reports.trial-balance'))->assertOk();
        $this->actingAs($user)->get(route('admin.gst-reports.index'))->assertOk();
    }

    public function test_ledger_account_code_is_unique_and_system_accounts_are_protected(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->seedChartOfAccounts();

        $this->actingAs($user)
            ->postJson(route('admin.ledger-accounts.store'), [
                'code' => '1100',
                'name' => 'Duplicate cash',
                'account_type' => 'asset',
            ])
            ->assertStatus(422);

        $system = LedgerAccount::query()->where('is_system', true)->firstOrFail();

        $this->actingAs($user)
            ->deleteJson(route('admin.ledger-accounts.destroy', $system->id))
            ->assertStatus(422);
    }

    /**
     * Seed the default chart of accounts and finance control-account settings.
     */
    protected function seedChartOfAccounts(): void
    {
        $this->seed(SystemSettingSeeder::class);
        $this->seed(LedgerAccountSeeder::class);
    }

    /**
     * Create and post a balanced manual journal voucher.
     */
    protected function postedManualVoucher(User $user, float $amount): JournalVoucher
    {
        $this->seedChartOfAccounts();

        $cash = LedgerAccount::query()->where('code', '1100')->firstOrFail();
        $sales = LedgerAccount::query()->where('code', '4100')->firstOrFail();

        $voucherId = (int) $this->actingAs($user)
            ->postJson(route('admin.journal-vouchers.store'), [
                'document_date' => now()->toDateString(),
                'voucher_type' => VoucherType::Journal->value,
                'narration' => 'Cash sale',
                'lines' => [
                    ['ledger_account_id' => $cash->id, 'debit' => $amount, 'credit' => 0],
                    ['ledger_account_id' => $sales->id, 'debit' => 0, 'credit' => $amount],
                ],
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user)->postJson(route('admin.journal-vouchers.post', $voucherId))->assertOk();

        return JournalVoucher::query()->with('lines')->findOrFail($voucherId);
    }

    /**
     * Confirm a sales order and raise a confirmed invoice against it.
     */
    protected function confirmedInvoice(User $user, float $quantity, float $rate): SalesInvoice
    {
        $customer = Party::factory()->create([
            'party_type' => PartyType::Customer,
            'gstin' => '27AAAAA0000A1Z5',
        ]);
        $warehouse = Warehouse::factory()->create(['is_leaf' => true]);
        $item = Item::factory()->create([
            'is_sellable' => true,
            'tracking_type' => TrackingType::None,
            'gst_rate' => 18,
            'selling_price' => $rate,
        ]);

        StockBalance::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'batch_id' => null,
            'batch_key' => 0,
            'qty' => $quantity * 2,
            'committed_qty' => 0,
            'on_order_qty' => 0,
            'value' => $quantity * 2 * $rate,
        ]);

        $orderId = (int) $this->actingAs($user)
            ->postJson(route('admin.sales-orders.store'), [
                'document_date' => now()->toDateString(),
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'place_of_supply_state_id' => State::query()->value('id'),
                'expected_delivery_date' => now()->addDays(5)->toDateString(),
                'items' => [[
                    'item_id' => $item->id,
                    'uom_id' => $item->stock_uom_id,
                    'quantity' => $quantity,
                    'rate' => $rate,
                    'gst_rate' => 18,
                ]],
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user)->postJson(route('admin.sales-orders.confirm', $orderId))->assertOk();
        $soLineId = (int) SalesOrder::query()->findOrFail($orderId)->items()->first()->id;

        $invoiceId = (int) $this->actingAs($user)
            ->postJson(route('admin.sales-invoices.store'), [
                'document_date' => now()->toDateString(),
                'sales_order_id' => $orderId,
                'items' => [[
                    'sales_order_item_id' => $soLineId,
                    'quantity' => $quantity,
                    'rate' => $rate,
                    'gst_rate' => 18,
                ]],
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user)->postJson(route('admin.sales-invoices.confirm', $invoiceId))->assertOk();

        return SalesInvoice::query()->with('items')->findOrFail($invoiceId);
    }
}
