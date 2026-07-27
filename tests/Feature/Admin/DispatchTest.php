<?php

namespace Tests\Feature\Admin;

use App\Enums\DeliveryChallanStatus;
use App\Enums\PartyType;
use App\Enums\SalesInvoiceStatus;
use App\Enums\SalesOrderStatus;
use App\Enums\TrackingType;
use App\Enums\TransportMode;
use App\Models\Batch;
use App\Models\Item;
use App\Models\Party;
use App\Models\SalesOrder;
use App\Models\State;
use App\Models\StockBalance;
use App\Models\Transporter;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature tests for M12 Delivery Challan / dispatch flow.
 */
class DispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_posts_stock_and_invoice_from_challan_does_not_double_issue(): void
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
            'selling_price' => 100,
        ]);

        StockBalance::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'batch_id' => null,
            'batch_key' => 0,
            'qty' => 50,
            'committed_qty' => 0,
            'on_order_qty' => 0,
            'value' => 5000,
        ]);

        $order = $this->actingAs($user)->postJson(route('admin.sales-orders.store'), [
            'document_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'place_of_supply_state_id' => $state->id,
            'expected_delivery_date' => now()->addDays(3)->toDateString(),
            'items' => [[
                'item_id' => $item->id,
                'uom_id' => $item->stock_uom_id,
                'quantity' => 10,
                'rate' => 100,
                'gst_rate' => 18,
            ]],
        ])->assertCreated();

        $orderId = $order->json('data.id');
        $this->actingAs($user)->postJson(route('admin.sales-orders.confirm', $orderId))->assertOk();
        $soLineId = SalesOrder::query()->findOrFail($orderId)->items()->first()->id;

        $challan = $this->actingAs($user)->postJson(route('admin.delivery-challans.store'), [
            'document_date' => now()->toDateString(),
            'sales_order_id' => $orderId,
            'transport_mode' => TransportMode::Road->value,
            'vehicle_number' => 'GJ01AB1234',
            'number_of_packages' => 2,
            'items' => [[
                'sales_order_item_id' => $soLineId,
                'quantity' => 10,
            ]],
        ])->assertCreated();

        $challanId = $challan->json('data.id');

        $this->actingAs($user)
            ->postJson(route('admin.delivery-challans.dispatch', $challanId))
            ->assertOk()
            ->assertJsonPath('data.status', DeliveryChallanStatus::Dispatched->value);

        $this->assertDatabaseHas('stock_balances', [
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty' => 40,
            'committed_qty' => 0,
        ]);

        $this->assertSame(SalesOrderStatus::Delivered, SalesOrder::query()->findOrFail($orderId)->status);

        $invoice = $this->actingAs($user)->postJson(route('admin.sales-invoices.store'), [
            'document_date' => now()->toDateString(),
            'delivery_challan_id' => $challanId,
            'sales_order_id' => $orderId,
            'items' => [[
                'sales_order_item_id' => $soLineId,
                'quantity' => 10,
                'rate' => 100,
                'gst_rate' => 18,
            ]],
        ])->assertCreated();

        $this->actingAs($user)
            ->postJson(route('admin.sales-invoices.confirm', $invoice->json('data.id')))
            ->assertOk()
            ->assertJsonPath('data.status', SalesInvoiceStatus::Confirmed->value);

        // Stock must not be reduced again (still 40).
        $this->assertDatabaseHas('stock_balances', [
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty' => 40,
        ]);

        $this->assertSame(SalesOrderStatus::Invoiced, SalesOrder::query()->findOrFail($orderId)->status);
    }

    public function test_eway_fields_required_when_value_exceeds_threshold(): void
    {
        $user = User::factory()->superAdmin()->create();
        $state = State::query()->first() ?? State::query()->create([
            'code' => '24',
            'name' => 'Gujarat',
            'is_active' => true,
        ]);
        $customer = Party::factory()->create([
            'party_type' => PartyType::Customer,
            'unlimited_credit' => true,
            'billing_state_id' => $state->id,
        ]);
        $warehouse = Warehouse::factory()->create(['is_leaf' => true, 'allow_negative_stock' => true]);
        $transporter = Transporter::factory()->create(['is_active' => true, 'gstin' => '24AABCT1332L1ZB']);
        $item = Item::factory()->create([
            'is_sellable' => true,
            'tracking_type' => TrackingType::None,
            'selling_price' => 10000,
            'gst_rate' => 0,
        ]);

        $order = $this->actingAs($user)->postJson(route('admin.sales-orders.store'), [
            'document_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'place_of_supply_state_id' => $state->id,
            'expected_delivery_date' => now()->addDays(2)->toDateString(),
            'items' => [[
                'item_id' => $item->id,
                'uom_id' => $item->stock_uom_id,
                'quantity' => 6,
                'rate' => 10000,
                'gst_rate' => 0,
            ]],
        ])->assertCreated();

        $orderId = $order->json('data.id');
        $this->actingAs($user)->postJson(route('admin.sales-orders.confirm', $orderId))->assertOk();
        $soLineId = SalesOrder::query()->findOrFail($orderId)->items()->first()->id;

        $challan = $this->actingAs($user)->postJson(route('admin.delivery-challans.store'), [
            'document_date' => now()->toDateString(),
            'sales_order_id' => $orderId,
            'transport_mode' => TransportMode::Road->value,
            'vehicle_number' => 'GJ01CD5678',
            'number_of_packages' => 1,
            'items' => [['sales_order_item_id' => $soLineId, 'quantity' => 6]],
        ])->assertCreated();

        $challanId = $challan->json('data.id');
        $this->assertTrue((bool) $challan->json('data.eway_required'));

        $this->actingAs($user)
            ->postJson(route('admin.delivery-challans.dispatch', $challanId))
            ->assertStatus(422);

        $this->actingAs($user)->putJson(route('admin.delivery-challans.update', $challanId), [
            'document_date' => now()->toDateString(),
            'transport_mode' => TransportMode::Road->value,
            'vehicle_number' => 'GJ01CD5678',
            'transporter_id' => $transporter->id,
            'distance_km' => 120,
            'eway_bill_number' => '123456789012',
            'number_of_packages' => 1,
            'items' => [['sales_order_item_id' => $soLineId, 'quantity' => 6]],
        ])->assertOk();

        $this->actingAs($user)
            ->postJson(route('admin.delivery-challans.dispatch', $challanId))
            ->assertOk()
            ->assertJsonPath('data.status', DeliveryChallanStatus::Dispatched->value);

        $payload = $this->actingAs($user)
            ->getJson(route('admin.delivery-challans.eway-payload', $challanId))
            ->assertOk()
            ->json('data');

        $this->assertSame('GJ01CD5678', $payload['vehicle_number']);
        $this->assertEqualsWithDelta(60000.0, (float) $payload['value'], 0.01);
    }

    public function test_challan_allocates_batches_fefo_when_no_batch_is_chosen(): void
    {
        $user = User::factory()->superAdmin()->create();
        $state = State::query()->first() ?? State::query()->create(['code' => '24', 'name' => 'Gujarat', 'is_active' => true]);
        $customer = Party::factory()->create([
            'party_type' => PartyType::Customer,
            'unlimited_credit' => true,
            'billing_state_id' => $state->id,
        ]);
        $warehouse = Warehouse::factory()->create(['is_leaf' => true, 'allow_negative_stock' => false]);
        $item = Item::factory()->create([
            'is_sellable' => true,
            'tracking_type' => TrackingType::Batch,
            'expiry_tracking' => true,
            'selling_price' => 100,
            'gst_rate' => 0,
        ]);

        $nearExpiry = Batch::factory()->create(['item_id' => $item->id, 'expiry_date' => now()->addDays(10)->toDateString()]);
        $farExpiry = Batch::factory()->create(['item_id' => $item->id, 'expiry_date' => now()->addDays(200)->toDateString()]);

        $this->seedBatchBalance($item->id, $warehouse->id, $farExpiry->id, 20);
        $this->seedBatchBalance($item->id, $warehouse->id, $nearExpiry->id, 6);

        $orderId = $this->actingAs($user)->postJson(route('admin.sales-orders.store'), [
            'document_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'place_of_supply_state_id' => $state->id,
            'expected_delivery_date' => now()->addDay()->toDateString(),
            'items' => [[
                'item_id' => $item->id,
                'uom_id' => $item->stock_uom_id,
                'quantity' => 10,
                'rate' => 100,
                'gst_rate' => 0,
            ]],
        ])->assertCreated()->json('data.id');

        $this->actingAs($user)->postJson(route('admin.sales-orders.confirm', $orderId))->assertOk();
        $soLineId = SalesOrder::query()->findOrFail($orderId)->items()->first()->id;

        $challanId = $this->actingAs($user)->postJson(route('admin.delivery-challans.store'), [
            'document_date' => now()->toDateString(),
            'sales_order_id' => $orderId,
            'transport_mode' => TransportMode::Road->value,
            'vehicle_number' => 'GJ01EF9012',
            'number_of_packages' => 1,
            'items' => [['sales_order_item_id' => $soLineId, 'quantity' => 10]],
        ])->assertCreated()->json('data.id');

        // FEFO must split the line: 6 from the near-expiry batch, 4 from the far-expiry batch.
        $this->assertDatabaseHas('delivery_challan_items', [
            'delivery_challan_id' => $challanId,
            'batch_id' => $nearExpiry->id,
            'quantity' => 6,
        ]);
        $this->assertDatabaseHas('delivery_challan_items', [
            'delivery_challan_id' => $challanId,
            'batch_id' => $farExpiry->id,
            'quantity' => 4,
        ]);

        $this->actingAs($user)->postJson(route('admin.delivery-challans.dispatch', $challanId))->assertOk();

        $this->assertDatabaseHas('stock_balances', [
            'item_id' => $item->id,
            'batch_id' => $nearExpiry->id,
            'qty' => 0,
        ]);
        $this->assertDatabaseHas('stock_balances', [
            'item_id' => $item->id,
            'batch_id' => $farExpiry->id,
            'qty' => 16,
        ]);
    }

    public function test_expired_batch_cannot_be_dispatched(): void
    {
        $user = User::factory()->superAdmin()->create();
        $state = State::query()->first() ?? State::query()->create(['code' => '24', 'name' => 'Gujarat', 'is_active' => true]);
        $customer = Party::factory()->create([
            'party_type' => PartyType::Customer,
            'unlimited_credit' => true,
            'billing_state_id' => $state->id,
        ]);
        $warehouse = Warehouse::factory()->create(['is_leaf' => true, 'allow_negative_stock' => false]);
        $item = Item::factory()->create([
            'is_sellable' => true,
            'tracking_type' => TrackingType::Batch,
            'expiry_tracking' => true,
            'selling_price' => 100,
            'gst_rate' => 0,
        ]);

        $expired = Batch::factory()->create(['item_id' => $item->id, 'expiry_date' => now()->subDay()->toDateString()]);
        $this->seedBatchBalance($item->id, $warehouse->id, $expired->id, 50);

        $orderId = $this->actingAs($user)->postJson(route('admin.sales-orders.store'), [
            'document_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'place_of_supply_state_id' => $state->id,
            'expected_delivery_date' => now()->addDay()->toDateString(),
            'items' => [[
                'item_id' => $item->id,
                'uom_id' => $item->stock_uom_id,
                'quantity' => 5,
                'rate' => 100,
                'gst_rate' => 0,
            ]],
        ])->assertCreated()->json('data.id');

        $this->actingAs($user)->postJson(route('admin.sales-orders.confirm', $orderId))->assertOk();
        $soLineId = SalesOrder::query()->findOrFail($orderId)->items()->first()->id;

        // Explicit expired batch is rejected.
        $this->actingAs($user)->postJson(route('admin.delivery-challans.store'), [
            'document_date' => now()->toDateString(),
            'sales_order_id' => $orderId,
            'transport_mode' => TransportMode::Road->value,
            'vehicle_number' => 'GJ01EF9012',
            'number_of_packages' => 1,
            'items' => [['sales_order_item_id' => $soLineId, 'quantity' => 5, 'batch_id' => $expired->id]],
        ])->assertStatus(422);

        // FEFO finds no unexpired stock either.
        $this->actingAs($user)->postJson(route('admin.delivery-challans.store'), [
            'document_date' => now()->toDateString(),
            'sales_order_id' => $orderId,
            'transport_mode' => TransportMode::Road->value,
            'vehicle_number' => 'GJ01EF9012',
            'number_of_packages' => 1,
            'items' => [['sales_order_item_id' => $soLineId, 'quantity' => 5]],
        ])->assertStatus(422);
    }

    /**
     * Seed a batch-level stock balance row for dispatch tests.
     */
    protected function seedBatchBalance(int $itemId, int $warehouseId, int $batchId, float $qty): void
    {
        StockBalance::query()->create([
            'item_id' => $itemId,
            'warehouse_id' => $warehouseId,
            'batch_id' => $batchId,
            'batch_key' => $batchId,
            'qty' => $qty,
            'committed_qty' => 0,
            'on_order_qty' => 0,
            'value' => $qty * 80,
        ]);
    }

    public function test_pod_upload_marks_challan_delivered(): void
    {
        Storage::fake('local');
        $user = User::factory()->superAdmin()->create();
        $state = State::query()->first() ?? State::query()->create([
            'code' => '27',
            'name' => 'Maharashtra',
            'is_active' => true,
        ]);
        $customer = Party::factory()->create([
            'party_type' => PartyType::Customer,
            'unlimited_credit' => true,
            'billing_state_id' => $state->id,
        ]);
        $warehouse = Warehouse::factory()->create(['is_leaf' => true, 'allow_negative_stock' => true]);
        $item = Item::factory()->create(['is_sellable' => true, 'tracking_type' => TrackingType::None, 'selling_price' => 10]);

        $order = $this->actingAs($user)->postJson(route('admin.sales-orders.store'), [
            'document_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'place_of_supply_state_id' => $state->id,
            'expected_delivery_date' => now()->addDay()->toDateString(),
            'items' => [[
                'item_id' => $item->id,
                'uom_id' => $item->stock_uom_id,
                'quantity' => 1,
                'rate' => 10,
                'gst_rate' => 0,
            ]],
        ])->assertCreated();

        $orderId = $order->json('data.id');
        $this->actingAs($user)->postJson(route('admin.sales-orders.confirm', $orderId))->assertOk();
        $soLineId = SalesOrder::query()->findOrFail($orderId)->items()->first()->id;

        $challanId = $this->actingAs($user)->postJson(route('admin.delivery-challans.store'), [
            'document_date' => now()->toDateString(),
            'sales_order_id' => $orderId,
            'transport_mode' => TransportMode::Rail->value,
            'number_of_packages' => 1,
            'items' => [['sales_order_item_id' => $soLineId, 'quantity' => 1]],
        ])->json('data.id');

        $this->actingAs($user)->postJson(route('admin.delivery-challans.dispatch', $challanId))->assertOk();

        $this->actingAs($user)
            ->post(route('admin.delivery-challans.mark-delivered', $challanId), [
                'pod' => UploadedFile::fake()->create('pod.pdf', 100, 'application/pdf'),
            ])
            ->assertOk()
            ->assertJsonPath('data.status', DeliveryChallanStatus::Delivered->value);
    }
}
