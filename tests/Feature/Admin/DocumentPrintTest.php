<?php

namespace Tests\Feature\Admin;

use App\Enums\PartyType;
use App\Enums\TrackingType;
use App\Enums\TransportMode;
use App\Models\Item;
use App\Models\Party;
use App\Models\SalesOrder;
use App\Models\State;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\DocumentPrintService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for the shared commercial-document print views (A4 gap-close).
 */
class DocumentPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_order_print_view_renders(): void
    {
        $user = User::factory()->superAdmin()->create();
        $supplier = Party::factory()->create(['party_type' => PartyType::Supplier]);
        $warehouse = Warehouse::factory()->create(['is_leaf' => true]);
        $item = Item::factory()->create(['is_purchasable' => true, 'tracking_type' => TrackingType::None]);

        $poId = $this->actingAs($user)->postJson(route('admin.purchase-orders.store'), [
            'document_date' => now()->toDateString(),
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'expected_delivery_date' => now()->addDays(3)->toDateString(),
            'items' => [[
                'item_id' => $item->id,
                'uom_id' => $item->stock_uom_id,
                'quantity' => 12,
                'rate' => 250,
                'gst_rate' => 18,
            ]],
        ])->assertCreated()->json('data.id');

        $this->actingAs($user)
            ->get(route('admin.purchase-orders.print', $poId))
            ->assertOk()
            ->assertSee('Purchase Order')
            ->assertSee($supplier->party_name);
    }

    public function test_sales_documents_print_views_render(): void
    {
        $user = User::factory()->superAdmin()->create();
        $state = State::query()->first() ?? State::query()->create(['code' => '24', 'name' => 'Gujarat', 'is_active' => true]);
        $customer = Party::factory()->create([
            'party_type' => PartyType::Customer,
            'unlimited_credit' => true,
            'billing_state_id' => $state->id,
        ]);
        $warehouse = Warehouse::factory()->create(['is_leaf' => true]);
        $item = Item::factory()->create([
            'is_sellable' => true,
            'tracking_type' => TrackingType::None,
            'selling_price' => 100,
            'gst_rate' => 18,
        ]);

        StockBalance::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'batch_id' => null,
            'batch_key' => 0,
            'qty' => 100,
            'committed_qty' => 0,
            'on_order_qty' => 0,
            'value' => 8000,
        ]);

        $quotationId = $this->actingAs($user)->postJson(route('admin.sales-quotations.store'), [
            'document_date' => now()->toDateString(),
            'valid_until' => now()->addDays(15)->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'place_of_supply_state_id' => $state->id,
            'items' => [[
                'item_id' => $item->id,
                'uom_id' => $item->stock_uom_id,
                'quantity' => 5,
                'rate' => 100,
                'gst_rate' => 18,
            ]],
        ])->assertCreated()->json('data.id');

        $this->actingAs($user)
            ->get(route('admin.sales-quotations.print', $quotationId))
            ->assertOk()
            ->assertSee('Quotation');

        $orderId = $this->actingAs($user)->postJson(route('admin.sales-orders.store'), [
            'document_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'place_of_supply_state_id' => $state->id,
            'expected_delivery_date' => now()->addDays(2)->toDateString(),
            'items' => [[
                'item_id' => $item->id,
                'uom_id' => $item->stock_uom_id,
                'quantity' => 5,
                'rate' => 100,
                'gst_rate' => 18,
            ]],
        ])->assertCreated()->json('data.id');

        $this->actingAs($user)->postJson(route('admin.sales-orders.confirm', $orderId))->assertOk();
        $soLineId = SalesOrder::query()->findOrFail($orderId)->items()->first()->id;

        $challanId = $this->actingAs($user)->postJson(route('admin.delivery-challans.store'), [
            'document_date' => now()->toDateString(),
            'sales_order_id' => $orderId,
            'transport_mode' => TransportMode::Road->value,
            'vehicle_number' => 'GJ01AB1234',
            'number_of_packages' => 1,
            'items' => [['sales_order_item_id' => $soLineId, 'quantity' => 5]],
        ])->assertCreated()->json('data.id');

        $this->actingAs($user)->postJson(route('admin.delivery-challans.dispatch', $challanId))->assertOk();

        $this->actingAs($user)
            ->get(route('admin.delivery-challans.print', $challanId))
            ->assertOk()
            ->assertSee('Delivery Challan');

        $invoiceId = $this->actingAs($user)->postJson(route('admin.sales-invoices.store'), [
            'document_date' => now()->toDateString(),
            'delivery_challan_id' => $challanId,
            'sales_order_id' => $orderId,
            'items' => [[
                'sales_order_item_id' => $soLineId,
                'quantity' => 5,
                'rate' => 100,
                'gst_rate' => 18,
            ]],
        ])->assertCreated()->json('data.id');

        $this->actingAs($user)
            ->get(route('admin.sales-invoices.print', $invoiceId))
            ->assertOk()
            ->assertSee('Tax Invoice')
            ->assertSee('Amount in words');
    }

    public function test_amount_in_words_uses_indian_grouping(): void
    {
        $print = app(DocumentPrintService::class);

        $this->assertSame('Zero Rupees Only', $print->amountInWords(0));
        $this->assertSame('One Hundred Five Rupees and Fifty Paise Only', $print->amountInWords(105.50));
        $this->assertSame('One Lakh Twenty Three Thousand Four Hundred Fifty Six Rupees Only', $print->amountInWords(123456));
        $this->assertSame('Two Crore Rupees Only', $print->amountInWords(20000000));
    }
}
