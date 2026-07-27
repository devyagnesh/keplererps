<?php

namespace Tests\Feature\Admin;

use App\Enums\AdjustmentDirection;
use App\Enums\DocumentStatus;
use App\Enums\ItemType;
use App\Enums\StockTransactionType;
use App\Enums\TrackingType;
use App\Models\Batch;
use App\Models\Item;
use App\Models\OpeningStock;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Feature tests for M08 inventory core.
 */
class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_stock_can_be_posted_and_updates_balance(): void
    {
        $user = User::factory()->superAdmin()->create();
        $warehouse = Warehouse::factory()->create(['is_leaf' => true]);
        $item = Item::factory()->create([
            'item_type' => ItemType::RawMaterial,
            'tracking_type' => TrackingType::None,
            'standard_cost' => 10,
        ]);

        $create = $this->actingAs($user)
            ->postJson(route('admin.opening-stocks.store'), [
                'document_date' => now()->toDateString(),
                'warehouse_id' => $warehouse->id,
                'remarks' => 'Go-live',
                'items' => [
                    [
                        'item_id' => $item->id,
                        'quantity' => 100,
                        'rate' => 10,
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('status', true);

        $openingStockId = $create->json('data.id');

        $this->actingAs($user)
            ->postJson(route('admin.opening-stocks.post', $openingStockId))
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertDatabaseHas('stock_ledger_entries', [
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty_in' => 100,
        ]);

        $this->assertDatabaseHas('stock_balances', [
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty' => 100,
            'value' => 1000,
        ]);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'has_stock' => 1,
            'has_transactions' => 1,
        ]);

        $this->assertSame(DocumentStatus::Posted, OpeningStock::query()->find($openingStockId)->status);
    }

    public function test_serial_tracked_opening_stock_creates_serial_and_posts_ledger(): void
    {
        $user = User::factory()->superAdmin()->create();
        $warehouse = Warehouse::factory()->create(['is_leaf' => true]);
        $item = Item::factory()->create([
            'tracking_type' => TrackingType::Serial,
            'standard_cost' => 250,
        ]);

        $create = $this->actingAs($user)
            ->postJson(route('admin.opening-stocks.store'), [
                'document_date' => now()->toDateString(),
                'warehouse_id' => $warehouse->id,
                'items' => [
                    [
                        'item_id' => $item->id,
                        'serial_no' => 'SN-OS-001',
                        'quantity' => 1,
                        'rate' => 250,
                    ],
                ],
            ])
            ->assertCreated();

        $this->actingAs($user)
            ->postJson(route('admin.opening-stocks.post', $create->json('data.id')))
            ->assertOk();

        $this->assertDatabaseHas('serials', [
            'item_id' => $item->id,
            'serial_no' => 'SN-OS-001',
            'status' => 'in_stock',
        ]);

        $this->assertDatabaseHas('stock_ledger_entries', [
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty_in' => 1,
        ]);

        $this->assertNotNull(
            \App\Models\StockLedgerEntry::query()
                ->where('item_id', $item->id)
                ->whereNotNull('serial_id')
                ->first()
        );
    }

    public function test_stock_balance_summary_tallies_with_ledger_after_opening_stock(): void
    {
        $user = User::factory()->superAdmin()->create();
        $warehouse = Warehouse::factory()->create(['is_leaf' => true]);
        $item = Item::factory()->create([
            'tracking_type' => TrackingType::None,
            'standard_cost' => 12.5,
        ]);

        $create = $this->actingAs($user)
            ->postJson(route('admin.opening-stocks.store'), [
                'document_date' => now()->toDateString(),
                'warehouse_id' => $warehouse->id,
                'items' => [
                    ['item_id' => $item->id, 'quantity' => 80, 'rate' => 12.5],
                ],
            ])
            ->assertCreated();

        $this->actingAs($user)
            ->postJson(route('admin.opening-stocks.post', $create->json('data.id')))
            ->assertOk();

        $summary = $this->actingAs($user)
            ->getJson(route('admin.stock-balances.summary', ['warehouse_id' => $warehouse->id]))
            ->assertOk()
            ->json('data');

        $ledgerQty = (float) \App\Models\StockLedgerEntry::query()
            ->where('warehouse_id', $warehouse->id)
            ->selectRaw('COALESCE(SUM(qty_in - qty_out), 0) as qty')
            ->value('qty');

        $ledgerValue = (float) \App\Models\StockLedgerEntry::query()
            ->where('warehouse_id', $warehouse->id)
            ->selectRaw('COALESCE(SUM(CASE WHEN qty_in > 0 THEN value ELSE -value END), 0) as value')
            ->value('value');

        $this->assertEqualsWithDelta(80.0, (float) $summary['total_qty'], 0.0001);
        $this->assertEqualsWithDelta(1000.0, (float) $summary['total_value'], 0.01);
        $this->assertEqualsWithDelta($ledgerQty, (float) $summary['total_qty'], 0.0001);
        $this->assertEqualsWithDelta($ledgerValue, (float) $summary['total_value'], 0.01);
    }

    public function test_negative_stock_is_blocked_by_default(): void
    {
        $user = User::factory()->superAdmin()->create();
        $warehouse = Warehouse::factory()->create([
            'is_leaf' => true,
            'allow_negative_stock' => false,
        ]);
        $item = Item::factory()->create([
            'tracking_type' => TrackingType::None,
        ]);

        StockBalance::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'batch_id' => null,
            'batch_key' => 0,
            'qty' => 5,
            'committed_qty' => 0,
            'on_order_qty' => 0,
            'value' => 50,
        ]);

        $create = $this->actingAs($user)
            ->postJson(route('admin.stock-adjustments.store'), [
                'document_date' => now()->toDateString(),
                'warehouse_id' => $warehouse->id,
                'reason' => 'Physical shortage',
                'items' => [
                    [
                        'item_id' => $item->id,
                        'direction' => AdjustmentDirection::Decrease->value,
                        'quantity' => 10,
                        'rate' => 10,
                    ],
                ],
            ])
            ->assertCreated();

        $this->actingAs($user)
            ->postJson(route('admin.stock-adjustments.post', $create->json('data.id')))
            ->assertStatus(422);
    }

    public function test_stock_transfer_moves_quantity_between_warehouses(): void
    {
        $user = User::factory()->superAdmin()->create();
        $from = Warehouse::factory()->create(['is_leaf' => true]);
        $to = Warehouse::factory()->create(['is_leaf' => true]);
        $item = Item::factory()->create(['tracking_type' => TrackingType::None]);

        $opening = $this->actingAs($user)
            ->postJson(route('admin.opening-stocks.store'), [
                'document_date' => now()->toDateString(),
                'warehouse_id' => $from->id,
                'items' => [['item_id' => $item->id, 'quantity' => 40, 'rate' => 5]],
            ])
            ->assertCreated();

        $this->actingAs($user)
            ->postJson(route('admin.opening-stocks.post', $opening->json('data.id')))
            ->assertOk();

        $transfer = $this->actingAs($user)
            ->postJson(route('admin.stock-transfers.store'), [
                'document_date' => now()->toDateString(),
                'from_warehouse_id' => $from->id,
                'to_warehouse_id' => $to->id,
                'items' => [['item_id' => $item->id, 'quantity' => 15]],
            ])
            ->assertCreated();

        $this->actingAs($user)
            ->postJson(route('admin.stock-transfers.post', $transfer->json('data.id')))
            ->assertOk();

        $this->assertDatabaseHas('stock_balances', [
            'item_id' => $item->id,
            'warehouse_id' => $from->id,
            'qty' => 25,
        ]);
        $this->assertDatabaseHas('stock_balances', [
            'item_id' => $item->id,
            'warehouse_id' => $to->id,
            'qty' => 15,
        ]);
    }

    public function test_fefo_allocation_prefers_earliest_expiry_and_skips_expired_batches(): void
    {
        $warehouse = Warehouse::factory()->create(['is_leaf' => true]);
        $item = Item::factory()->create([
            'tracking_type' => TrackingType::Batch,
            'expiry_tracking' => true,
        ]);

        $expired = Batch::factory()->create(['item_id' => $item->id, 'expiry_date' => now()->subDay()->toDateString()]);
        $soon = Batch::factory()->create(['item_id' => $item->id, 'expiry_date' => now()->addDays(5)->toDateString()]);
        $later = Batch::factory()->create(['item_id' => $item->id, 'expiry_date' => now()->addDays(90)->toDateString()]);
        $undated = Batch::factory()->create(['item_id' => $item->id, 'expiry_date' => null]);

        foreach ([$expired, $soon, $later, $undated] as $batch) {
            StockBalance::query()->create([
                'item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
                'batch_id' => $batch->id,
                'batch_key' => $batch->id,
                'qty' => 10,
                'committed_qty' => 0,
                'on_order_qty' => 0,
                'value' => 100,
            ]);
        }

        $allocations = app(StockLedgerService::class)->allocateFefo($item->id, $warehouse->id, 25);

        $this->assertSame(
            [$soon->id, $later->id, $undated->id],
            array_column($allocations, 'batch_id')
        );
        $this->assertSame([10.0, 10.0, 5.0], array_map('floatval', array_column($allocations, 'quantity')));
    }

    public function test_ledger_rejects_issuing_an_expired_batch(): void
    {
        $warehouse = Warehouse::factory()->create(['is_leaf' => true, 'allow_negative_stock' => true]);
        $item = Item::factory()->create([
            'tracking_type' => TrackingType::Batch,
            'expiry_tracking' => true,
        ]);
        $expired = Batch::factory()->create(['item_id' => $item->id, 'expiry_date' => now()->subDay()->toDateString()]);

        StockBalance::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'batch_id' => $expired->id,
            'batch_key' => $expired->id,
            'qty' => 20,
            'committed_qty' => 0,
            'on_order_qty' => 0,
            'value' => 200,
        ]);

        $this->expectException(ValidationException::class);

        app(StockLedgerService::class)->post([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'batch_id' => $expired->id,
            'transaction_type' => StockTransactionType::MaterialIssue,
            'qty_out' => 5,
            'source' => $item,
        ]);
    }

    public function test_availability_endpoint_reports_free_quantity_from_store_warehouses_only(): void
    {
        $user = User::factory()->superAdmin()->create();
        $store = Warehouse::factory()->create(['is_leaf' => true, 'warehouse_type' => 'store']);
        $quarantine = Warehouse::factory()->create(['is_leaf' => true, 'warehouse_type' => 'quarantine']);
        $item = Item::factory()->create(['tracking_type' => TrackingType::None]);

        StockBalance::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $store->id,
            'batch_id' => null,
            'batch_key' => 0,
            'qty' => 100,
            'committed_qty' => 30,
            'on_order_qty' => 25,
            'value' => 1000,
        ]);

        StockBalance::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $quarantine->id,
            'batch_id' => null,
            'batch_key' => 0,
            'qty' => 50,
            'committed_qty' => 0,
            'on_order_qty' => 0,
            'value' => 500,
        ]);

        $data = $this->actingAs($user)
            ->getJson(route('admin.stock-balances.availability', ['item_id' => $item->id]))
            ->assertOk()
            ->assertJsonPath('status', true)
            ->json('data');

        $this->assertEqualsWithDelta(100.0, (float) $data['physical_qty'], 0.0001);
        $this->assertEqualsWithDelta(30.0, (float) $data['committed_qty'], 0.0001);
        $this->assertEqualsWithDelta(25.0, (float) $data['on_order_qty'], 0.0001);
        $this->assertEqualsWithDelta(70.0, (float) $data['free_qty'], 0.0001);
    }

    public function test_availability_endpoint_can_be_scoped_to_a_single_warehouse(): void
    {
        $user = User::factory()->superAdmin()->create();
        $first = Warehouse::factory()->create(['is_leaf' => true, 'warehouse_type' => 'store']);
        $second = Warehouse::factory()->create(['is_leaf' => true, 'warehouse_type' => 'store']);
        $item = Item::factory()->create(['tracking_type' => TrackingType::None]);

        foreach ([[$first, 10], [$second, 40]] as [$warehouse, $qty]) {
            StockBalance::query()->create([
                'item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
                'batch_id' => null,
                'batch_key' => 0,
                'qty' => $qty,
                'committed_qty' => 0,
                'on_order_qty' => 0,
                'value' => $qty,
            ]);
        }

        $data = $this->actingAs($user)
            ->getJson(route('admin.stock-balances.availability', [
                'item_id' => $item->id,
                'warehouse_id' => $second->id,
            ]))
            ->assertOk()
            ->json('data');

        $this->assertEqualsWithDelta(40.0, (float) $data['free_qty'], 0.0001);
    }

    public function test_posted_opening_stock_cannot_be_edited(): void
    {
        $user = User::factory()->superAdmin()->create();
        $warehouse = Warehouse::factory()->create(['is_leaf' => true]);
        $item = Item::factory()->create(['tracking_type' => TrackingType::None]);

        $create = $this->actingAs($user)
            ->postJson(route('admin.opening-stocks.store'), [
                'document_date' => now()->toDateString(),
                'warehouse_id' => $warehouse->id,
                'items' => [['item_id' => $item->id, 'quantity' => 10, 'rate' => 2]],
            ])
            ->assertCreated();

        $id = $create->json('data.id');
        $this->actingAs($user)->postJson(route('admin.opening-stocks.post', $id))->assertOk();

        $this->actingAs($user)
            ->putJson(route('admin.opening-stocks.update', $id), [
                'document_date' => now()->toDateString(),
                'warehouse_id' => $warehouse->id,
                'items' => [['item_id' => $item->id, 'quantity' => 20, 'rate' => 2]],
            ])
            ->assertStatus(422);
    }
}
