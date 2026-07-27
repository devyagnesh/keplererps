<?php

namespace Tests\Feature\Admin;

use App\Enums\PartyType;
use App\Enums\QuotationStatus;
use App\Enums\SalesInvoiceStatus;
use App\Enums\SalesOrderStatus;
use App\Enums\TrackingType;
use App\Models\Item;
use App\Models\Party;
use App\Models\SalesOrder;
use App\Models\State;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for M06 Sales quotation → order → invoice flow.
 */
class SalesTest extends TestCase
{
    use RefreshDatabase;

    public function test_quotation_converts_to_sales_order_and_confirm_commits_stock(): void
    {
        $user = User::factory()->superAdmin()->create();
        $state = State::query()->first() ?? State::query()->create([
            'code' => '24',
            'name' => 'Gujarat',
            'is_active' => true,
        ]);
        $customer = Party::factory()->create([
            'party_type' => PartyType::Customer,
            'billing_state_id' => $state->id,
            'unlimited_credit' => true,
        ]);
        $warehouse = Warehouse::factory()->create(['is_leaf' => true]);
        $item = Item::factory()->create([
            'is_sellable' => true,
            'tracking_type' => TrackingType::None,
            'gst_rate' => 18,
            'selling_price' => 100,
        ]);

        $quote = $this->actingAs($user)
            ->postJson(route('admin.sales-quotations.store'), [
                'document_date' => now()->toDateString(),
                'valid_until' => now()->addDays(15)->toDateString(),
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'place_of_supply_state_id' => $state->id,
                'items' => [[
                    'item_id' => $item->id,
                    'uom_id' => $item->stock_uom_id,
                    'quantity' => 10,
                    'rate' => 100,
                    'discount_percent' => 0,
                    'gst_rate' => 18,
                ]],
            ])
            ->assertCreated()
            ->assertJsonPath('status', true);

        $quoteId = $quote->json('data.id');

        $this->actingAs($user)
            ->postJson(route('admin.sales-quotations.mark-sent', $quoteId))
            ->assertOk()
            ->assertJsonPath('data.status', QuotationStatus::Sent->value);

        $convert = $this->actingAs($user)
            ->postJson(route('admin.sales-quotations.convert', $quoteId))
            ->assertOk();

        $orderId = $convert->json('data.id');
        $this->assertDatabaseHas('sales_quotations', [
            'id' => $quoteId,
            'status' => QuotationStatus::Converted->value,
            'converted_sales_order_id' => $orderId,
        ]);

        $this->actingAs($user)
            ->postJson(route('admin.sales-orders.confirm', $orderId))
            ->assertOk()
            ->assertJsonPath('data.status', SalesOrderStatus::Confirmed->value);

        $this->assertDatabaseHas('stock_balances', [
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'committed_qty' => 10,
        ]);
    }

    public function test_invoice_confirm_posts_delivery_and_releases_commitment(): void
    {
        $user = User::factory()->superAdmin()->create();
        $state = State::query()->first() ?? State::query()->create([
            'code' => '24',
            'name' => 'Gujarat',
            'is_active' => true,
        ]);
        $customer = Party::factory()->create([
            'party_type' => PartyType::Customer,
            'billing_state_id' => $state->id,
            'unlimited_credit' => true,
        ]);
        $warehouse = Warehouse::factory()->create(['is_leaf' => true, 'allow_negative_stock' => false]);
        $item = Item::factory()->create([
            'is_sellable' => true,
            'tracking_type' => TrackingType::None,
            'gst_rate' => 18,
            'selling_price' => 50,
        ]);

        StockBalance::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'batch_id' => null,
            'batch_key' => 0,
            'qty' => 20,
            'committed_qty' => 0,
            'on_order_qty' => 0,
            'value' => 1000,
        ]);

        $order = $this->actingAs($user)
            ->postJson(route('admin.sales-orders.store'), [
                'document_date' => now()->toDateString(),
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'place_of_supply_state_id' => $state->id,
                'expected_delivery_date' => now()->addDays(5)->toDateString(),
                'items' => [[
                    'item_id' => $item->id,
                    'uom_id' => $item->stock_uom_id,
                    'quantity' => 8,
                    'rate' => 50,
                    'discount_percent' => 0,
                    'gst_rate' => 18,
                ]],
            ])
            ->assertCreated();

        $orderId = $order->json('data.id');
        $this->actingAs($user)->postJson(route('admin.sales-orders.confirm', $orderId))->assertOk();

        $soLineId = SalesOrder::query()->findOrFail($orderId)->items()->first()->id;

        $invoice = $this->actingAs($user)
            ->postJson(route('admin.sales-invoices.store'), [
                'document_date' => now()->toDateString(),
                'sales_order_id' => $orderId,
                'items' => [[
                    'sales_order_item_id' => $soLineId,
                    'quantity' => 8,
                    'rate' => 50,
                    'discount_percent' => 0,
                    'gst_rate' => 18,
                ]],
            ])
            ->assertCreated();

        $this->actingAs($user)
            ->postJson(route('admin.sales-invoices.confirm', $invoice->json('data.id')))
            ->assertOk()
            ->assertJsonPath('data.status', SalesInvoiceStatus::Confirmed->value);

        $this->assertDatabaseHas('stock_ledger_entries', [
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty_out' => 8,
        ]);

        $this->assertDatabaseHas('stock_balances', [
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty' => 12,
            'committed_qty' => 0,
        ]);

        $this->assertSame(SalesOrderStatus::Invoiced, SalesOrder::query()->findOrFail($orderId)->status);
    }

    public function test_credit_limit_puts_order_on_pending_approval(): void
    {
        $user = User::factory()->superAdmin()->create();
        $state = State::query()->first() ?? State::query()->create([
            'code' => '27',
            'name' => 'Maharashtra',
            'is_active' => true,
        ]);
        $customer = Party::factory()->create([
            'party_type' => PartyType::Customer,
            'billing_state_id' => $state->id,
            'unlimited_credit' => false,
            'credit_limit' => 100,
        ]);
        $warehouse = Warehouse::factory()->create(['is_leaf' => true]);
        $item = Item::factory()->create([
            'is_sellable' => true,
            'tracking_type' => TrackingType::None,
            'selling_price' => 200,
            'gst_rate' => 0,
        ]);

        $this->actingAs($user)
            ->postJson(route('admin.sales-orders.store'), [
                'document_date' => now()->toDateString(),
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'place_of_supply_state_id' => $state->id,
                'expected_delivery_date' => now()->addDays(2)->toDateString(),
                'items' => [[
                    'item_id' => $item->id,
                    'uom_id' => $item->stock_uom_id,
                    'quantity' => 1,
                    'rate' => 200,
                    'gst_rate' => 0,
                ]],
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', SalesOrderStatus::PendingApproval->value)
            ->assertJsonPath('data.credit_hold', true);
    }
}
