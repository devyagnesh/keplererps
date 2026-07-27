<?php

namespace Tests\Feature\Admin;

use App\Enums\PartyStatus;
use App\Enums\PartyType;
use App\Enums\PurchaseIndentStatus;
use App\Enums\RfqStatus;
use App\Models\Item;
use App\Models\ItemWarehouseSetting;
use App\Models\Party;
use App\Models\PurchaseIndent;
use App\Models\PurchaseRfq;
use App\Models\ScheduledReport;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\EinvoiceService;
use App\Services\StatutoryPayrollService;
use App\Services\UomConversionService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Final SRS completeness coverage (RFQ, dual UOM, statutory, schedules, e-invoice, webhook).
 */
class SrsCompleteAllTest extends TestCase
{
    use RefreshDatabase;

    public function test_rfq_can_be_created_quoted_and_awarded_to_po(): void
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
        $supplier = Party::factory()->create([
            'party_type' => PartyType::Supplier,
            'status' => PartyStatus::Active,
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.purchase-indents.store'), ['warehouse_id' => $warehouse->id])
            ->assertCreated();

        $indent = PurchaseIndent::query()->firstOrFail();
        $this->actingAs($admin)
            ->postJson(route('admin.purchase-indents.approve', $indent))
            ->assertOk();

        $this->actingAs($admin)
            ->postJson(route('admin.purchase-indents.rfq', $indent))
            ->assertCreated();

        $rfq = PurchaseRfq::query()->firstOrFail();
        $this->assertSame(RfqStatus::Draft, $rfq->status);

        $this->actingAs($admin)
            ->postJson(route('admin.purchase-rfqs.mark-sent', $rfq))
            ->assertOk();

        $rates = [];
        foreach ($rfq->items as $line) {
            $rates[$line->id] = 10;
        }

        $this->actingAs($admin)
            ->postJson(route('admin.purchase-rfqs.add-quote', $rfq), [
                'supplier_id' => $supplier->id,
                'rates' => $rates,
                'freight_amount' => 0,
                'lead_time_days' => 5,
            ])
            ->assertCreated();

        $quote = $rfq->fresh()->quotes()->firstOrFail();

        $this->actingAs($admin)
            ->postJson(route('admin.purchase-rfqs.award', [$rfq, $quote]), [
                'create_po' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.purchase_order_id', fn ($id) => $id !== null);

        $this->assertSame(RfqStatus::Awarded, $rfq->fresh()->status);
        $this->assertSame(PurchaseIndentStatus::Ordered, $indent->fresh()->status);
    }

    public function test_uom_conversion_and_statutory_helpers(): void
    {
        $statutory = app(StatutoryPayrollService::class);
        $this->assertSame(120.0, $statutory->pfEmployee(1000));
        $this->assertSame(200.0, $statutory->professionalTax('GJ', 15000));
        $this->assertSame(0.0, $statutory->esiEmployee(25000));

        $uom = app(UomConversionService::class);
        $item = Item::factory()->create();
        $qty = $uom->toStockQty($item, 5, (int) $item->stock_uom_id);
        $this->assertSame(5.0, $qty);
    }

    public function test_scheduled_report_and_whatsapp_webhook_and_einvoice_dry_run(): void
    {
        $this->seed(SystemSettingSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->postJson(route('admin.scheduled-reports.store'), [
                'name' => 'Daily sales',
                'register_key' => 'sales',
                'frequency' => 'daily',
                'recipient_emails' => 'ops@example.com',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('scheduled_reports', ['name' => 'Daily sales']);

        $this->get(route('webhooks.whatsapp.verify', [
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'kepler-whatsapp-verify',
            'hub_challenge' => 'challenge-123',
        ]))->assertOk()->assertSee('challenge-123');

        $this->postJson(route('webhooks.whatsapp.handle'), [
            'entry' => [['changes' => [['value' => ['messages' => [['from' => '919999999999', 'text' => ['body' => 'hi']]]]]]]],
        ])->assertOk();

        $customer = Party::factory()->create([
            'party_type' => PartyType::Customer,
            'status' => PartyStatus::Active,
        ]);
        $warehouse = Warehouse::factory()->create(['is_leaf' => true, 'is_active' => true]);
        $stateId = (int) (\App\Models\State::query()->value('id') ?: \App\Models\State::query()->create([
            'code' => '24',
            'name' => 'Gujarat',
            'is_active' => true,
        ])->id);
        $order = \App\Models\SalesOrder::query()->create([
            'document_no' => 'SO-TEST-1',
            'document_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'place_of_supply_state_id' => $stateId,
            'expected_delivery_date' => now()->addDays(5)->toDateString(),
            'status' => 'draft',
            'tax_type' => 'cgst_sgst',
            'subtotal' => 100,
            'discount_total' => 0,
            'tax_total' => 18,
            'grand_total' => 118,
        ]);
        $invoice = \App\Models\SalesInvoice::query()->create([
            'document_no' => 'INV-TEST-1',
            'document_date' => now()->toDateString(),
            'sales_order_id' => $order->id,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'place_of_supply_state_id' => $stateId,
            'status' => 'draft',
            'tax_type' => 'gst',
            'subtotal' => 100,
            'discount_total' => 0,
            'tax_total' => 18,
            'round_off' => 0,
            'grand_total' => 118,
        ]);
        $result = app(EinvoiceService::class)->push($invoice);
        $this->assertContains($result['status'], ['queued', 'demo_pushed', 'dry_run']);
        $this->assertDatabaseHas('einvoice_logs', ['sales_invoice_id' => $invoice->id]);
    }
}
