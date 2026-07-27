<?php

namespace Tests\Feature\Admin;

use App\Enums\PartyType;
use App\Enums\PurchaseOrderStatus;
use App\Enums\TrackingType;
use App\Models\GoodsReceiptItem;
use App\Models\Item;
use App\Models\Party;
use App\Models\PurchaseOrder;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for M07 Purchase Order and GRN flow.
 */
class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_order_can_be_approved_and_grn_posts_stock(): void
    {
        $user = User::factory()->superAdmin()->create();
        $supplier = Party::factory()->create(['party_type' => PartyType::Supplier]);
        $warehouse = Warehouse::factory()->create(['is_leaf' => true]);
        $item = Item::factory()->create([
            'is_purchasable' => true,
            'tracking_type' => TrackingType::None,
            'gst_rate' => 18,
            'standard_cost' => 10,
        ]);

        $create = $this->actingAs($user)
            ->postJson(route('admin.purchase-orders.store'), [
                'document_date' => now()->toDateString(),
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'expected_delivery_date' => now()->addDays(5)->toDateString(),
                'items' => [
                    [
                        'item_id' => $item->id,
                        'uom_id' => $item->stock_uom_id,
                        'quantity' => 100,
                        'rate' => 10,
                        'gst_rate' => 18,
                        'tolerance_percent' => 5,
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('status', true);

        $poId = $create->json('data.id');

        $this->actingAs($user)
            ->postJson(route('admin.purchase-orders.approve', $poId))
            ->assertOk()
            ->assertJsonPath('data.status', PurchaseOrderStatus::Approved->value);

        $this->assertDatabaseHas('stock_balances', [
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'on_order_qty' => 100,
        ]);

        $po = PurchaseOrder::query()->with('items')->findOrFail($poId);
        $poLineId = $po->items->first()->id;

        $grn = $this->actingAs($user)
            ->postJson(route('admin.goods-receipts.store'), [
                'document_date' => now()->toDateString(),
                'purchase_order_id' => $poId,
                'supplier_invoice_no' => 'SUP-INV-1001',
                'supplier_invoice_date' => now()->toDateString(),
                'items' => [
                    [
                        'purchase_order_item_id' => $poLineId,
                        'received_qty' => 40,
                        'accepted_qty' => 40,
                        'rejected_qty' => 0,
                        'rate' => 10,
                    ],
                ],
            ])
            ->assertCreated();

        $this->actingAs($user)
            ->postJson(route('admin.goods-receipts.post', $grn->json('data.id')))
            ->assertOk();

        $this->assertDatabaseHas('stock_ledger_entries', [
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty_in' => 40,
        ]);

        $this->assertDatabaseHas('stock_balances', [
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty' => 40,
        ]);

        $po->refresh();
        $this->assertSame(PurchaseOrderStatus::PartiallyReceived, $po->status);
        $this->assertEqualsWithDelta(40.0, (float) $po->items()->first()->received_qty, 0.0001);
    }

    public function test_over_receipt_beyond_tolerance_is_blocked(): void
    {
        $user = User::factory()->superAdmin()->create();
        $supplier = Party::factory()->create(['party_type' => PartyType::Supplier]);
        $warehouse = Warehouse::factory()->create(['is_leaf' => true]);
        $item = Item::factory()->create(['is_purchasable' => true, 'tracking_type' => TrackingType::None]);

        $create = $this->actingAs($user)->postJson(route('admin.purchase-orders.store'), [
            'document_date' => now()->toDateString(),
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'expected_delivery_date' => now()->addDays(3)->toDateString(),
            'items' => [[
                'item_id' => $item->id,
                'uom_id' => $item->stock_uom_id,
                'quantity' => 10,
                'rate' => 5,
                'tolerance_percent' => 0,
            ]],
        ])->assertCreated();

        $poId = $create->json('data.id');
        $this->actingAs($user)->postJson(route('admin.purchase-orders.approve', $poId))->assertOk();
        $poLineId = PurchaseOrder::query()->findOrFail($poId)->items()->first()->id;

        $this->actingAs($user)->postJson(route('admin.goods-receipts.store'), [
            'document_date' => now()->toDateString(),
            'purchase_order_id' => $poId,
            'supplier_invoice_no' => 'SUP-INV-2002',
            'supplier_invoice_date' => now()->toDateString(),
            'items' => [[
                'purchase_order_item_id' => $poLineId,
                'received_qty' => 11,
                'accepted_qty' => 11,
                'rejected_qty' => 0,
                'rate' => 5,
            ]],
        ])->assertStatus(422);
    }

    public function test_grn_charges_are_allocated_into_the_landed_rate_on_post(): void
    {
        $user = User::factory()->superAdmin()->create();
        $supplier = Party::factory()->create(['party_type' => PartyType::Supplier]);
        $warehouse = Warehouse::factory()->create(['is_leaf' => true]);
        $itemA = Item::factory()->create(['is_purchasable' => true, 'tracking_type' => TrackingType::None]);
        $itemB = Item::factory()->create(['is_purchasable' => true, 'tracking_type' => TrackingType::None]);

        $poId = $this->actingAs($user)->postJson(route('admin.purchase-orders.store'), [
            'document_date' => now()->toDateString(),
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'expected_delivery_date' => now()->addDays(3)->toDateString(),
            'items' => [
                ['item_id' => $itemA->id, 'uom_id' => $itemA->stock_uom_id, 'quantity' => 10, 'rate' => 100],
                ['item_id' => $itemB->id, 'uom_id' => $itemB->stock_uom_id, 'quantity' => 10, 'rate' => 300],
            ],
        ])->assertCreated()->json('data.id');

        $this->actingAs($user)->postJson(route('admin.purchase-orders.approve', $poId))->assertOk();
        $poLines = PurchaseOrder::query()->with('items')->findOrFail($poId)->items;

        // Freight of 800 splits 25/75 on line value (1000 vs 3000).
        $grnId = $this->actingAs($user)->postJson(route('admin.goods-receipts.store'), [
            'document_date' => now()->toDateString(),
            'purchase_order_id' => $poId,
            'supplier_invoice_no' => 'SUP-INV-3003',
            'supplier_invoice_date' => now()->toDateString(),
            'freight_charges' => 800,
            'other_charges' => 0,
            'charge_allocation_basis' => 'value',
            'items' => [
                ['purchase_order_item_id' => $poLines[0]->id, 'received_qty' => 10, 'accepted_qty' => 10, 'rate' => 100],
                ['purchase_order_item_id' => $poLines[1]->id, 'received_qty' => 10, 'accepted_qty' => 10, 'rate' => 300],
            ],
        ])->assertCreated()->json('data.id');

        $this->actingAs($user)->postJson(route('admin.goods-receipts.post', $grnId))->assertOk();

        $lines = GoodsReceiptItem::query()->where('goods_receipt_id', $grnId)->orderBy('sort_order')->get();

        $this->assertEqualsWithDelta(200.0, (float) $lines[0]->allocated_charge, 0.01);
        $this->assertEqualsWithDelta(120.0, (float) $lines[0]->landed_rate, 0.0001);
        $this->assertEqualsWithDelta(600.0, (float) $lines[1]->allocated_charge, 0.01);
        $this->assertEqualsWithDelta(360.0, (float) $lines[1]->landed_rate, 0.0001);

        $this->assertDatabaseHas('stock_ledger_entries', [
            'item_id' => $itemA->id,
            'rate' => 120,
            'value' => 1200,
        ]);

        $balanceB = StockBalance::query()
            ->where('item_id', $itemB->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('batch_key', 0)
            ->firstOrFail();

        $this->assertEqualsWithDelta(3600.0, (float) $balanceB->value, 0.01);
    }

    public function test_grn_charges_can_be_allocated_on_accepted_quantity(): void
    {
        $user = User::factory()->superAdmin()->create();
        $supplier = Party::factory()->create(['party_type' => PartyType::Supplier]);
        $warehouse = Warehouse::factory()->create(['is_leaf' => true]);
        $itemA = Item::factory()->create(['is_purchasable' => true, 'tracking_type' => TrackingType::None]);
        $itemB = Item::factory()->create(['is_purchasable' => true, 'tracking_type' => TrackingType::None]);

        $poId = $this->actingAs($user)->postJson(route('admin.purchase-orders.store'), [
            'document_date' => now()->toDateString(),
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'expected_delivery_date' => now()->addDays(3)->toDateString(),
            'items' => [
                ['item_id' => $itemA->id, 'uom_id' => $itemA->stock_uom_id, 'quantity' => 10, 'rate' => 100],
                ['item_id' => $itemB->id, 'uom_id' => $itemB->stock_uom_id, 'quantity' => 30, 'rate' => 300],
            ],
        ])->assertCreated()->json('data.id');

        $this->actingAs($user)->postJson(route('admin.purchase-orders.approve', $poId))->assertOk();
        $poLines = PurchaseOrder::query()->with('items')->findOrFail($poId)->items;

        $grnId = $this->actingAs($user)->postJson(route('admin.goods-receipts.store'), [
            'document_date' => now()->toDateString(),
            'purchase_order_id' => $poId,
            'supplier_invoice_no' => 'SUP-INV-4004',
            'supplier_invoice_date' => now()->toDateString(),
            'freight_charges' => 300,
            'other_charges' => 100,
            'charge_allocation_basis' => 'quantity',
            'items' => [
                ['purchase_order_item_id' => $poLines[0]->id, 'received_qty' => 10, 'accepted_qty' => 10, 'rate' => 100],
                ['purchase_order_item_id' => $poLines[1]->id, 'received_qty' => 30, 'accepted_qty' => 30, 'rate' => 300],
            ],
        ])->assertCreated()->json('data.id');

        $this->actingAs($user)->postJson(route('admin.goods-receipts.post', $grnId))->assertOk();

        $lines = GoodsReceiptItem::query()->where('goods_receipt_id', $grnId)->orderBy('sort_order')->get();

        $this->assertEqualsWithDelta(100.0, (float) $lines[0]->allocated_charge, 0.01);
        $this->assertEqualsWithDelta(110.0, (float) $lines[0]->landed_rate, 0.0001);
        $this->assertEqualsWithDelta(300.0, (float) $lines[1]->allocated_charge, 0.01);
        $this->assertEqualsWithDelta(310.0, (float) $lines[1]->landed_rate, 0.0001);
    }

    public function test_purchase_suggestions_list_items_below_reorder(): void
    {
        $user = User::factory()->superAdmin()->create();
        $warehouse = Warehouse::factory()->create(['is_leaf' => true]);
        $item = Item::factory()->create(['is_purchasable' => true, 'is_active' => true]);

        $item->warehouseSettings()->create([
            'warehouse_id' => $warehouse->id,
            'reorder_level' => 50,
            'reorder_qty' => 100,
        ]);

        StockBalance::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'batch_id' => null,
            'batch_key' => 0,
            'qty' => 10,
            'committed_qty' => 0,
            'on_order_qty' => 0,
            'value' => 100,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('admin.purchase-suggestions.data', ['warehouse_id' => $warehouse->id]))
            ->assertOk()
            ->json('data');

        $this->assertNotEmpty($response);
        $this->assertSame($item->id, $response[0]['item_id']);
        $this->assertEqualsWithDelta(100.0, (float) $response[0]['suggested_qty'], 0.0001);
    }
}
