<?php

namespace Tests\Feature\Admin;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Enums\ItemType;
use App\Enums\MaintenanceOrderStatus;
use App\Enums\MaintenanceOrderType;
use App\Enums\StockTransactionType;
use App\Enums\TrackingType;
use App\Models\Item;
use App\Models\MaintenanceOrder;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkCentre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for M11 Machine / Mould maintenance.
 */
class MaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_can_be_created_with_service_intervals(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)->postJson(route('admin.work-centres.store'), [
            'code' => 'INJ-01',
            'name' => 'Injection Press 01',
            'asset_type' => AssetType::Machine->value,
            'status' => AssetStatus::Active->value,
            'machine_rate_per_hour' => 250,
            'labour_rate_per_hour' => 80,
            'service_interval_days' => 30,
            'service_interval_cycles' => 10000,
            'is_active' => true,
        ])->assertCreated()->assertJsonPath('status', true);

        $this->assertDatabaseHas('work_centres', [
            'code' => 'INJ-01',
            'asset_type' => AssetType::Machine->value,
            'service_interval_days' => 30,
        ]);
    }

    public function test_breakdown_order_stops_asset_and_blocks_production_flag(): void
    {
        $user = User::factory()->superAdmin()->create();
        $asset = WorkCentre::factory()->create([
            'code' => 'MLD-01',
            'asset_type' => AssetType::Mould,
            'status' => AssetStatus::Active,
            'cavity_count' => 2,
            'service_interval_cycles' => 100,
            'cycles_used' => 0,
        ]);

        $create = $this->actingAs($user)->postJson(route('admin.maintenance-orders.store'), [
            'document_date' => now()->toDateString(),
            'order_type' => MaintenanceOrderType::Breakdown->value,
            'work_centre_id' => $asset->id,
            'reason' => 'Hydraulic leak',
        ])->assertCreated();

        $asset->refresh();
        $this->assertSame(AssetStatus::UnderBreakdown, $asset->status);
        $this->assertFalse($asset->status->canReceiveProduction());

        $orderId = $create->json('data.id');
        $this->actingAs($user)
            ->postJson(route('admin.maintenance-orders.close', $orderId), [
                'action_taken' => 'Seal replaced',
            ])
            ->assertOk();

        $asset->refresh();
        $this->assertSame(AssetStatus::Active, $asset->status);
        $this->assertSame(MaintenanceOrderStatus::Closed, MaintenanceOrder::query()->findOrFail($orderId)->status);
    }

    public function test_spare_parts_issue_reduces_stock(): void
    {
        $user = User::factory()->superAdmin()->create();
        $warehouse = Warehouse::factory()->create(['is_leaf' => true]);
        $spare = Item::factory()->create([
            'item_type' => ItemType::SparePart,
            'tracking_type' => TrackingType::None,
            'is_purchasable' => true,
            'standard_cost' => 25,
        ]);
        StockBalance::query()->create([
            'item_id' => $spare->id,
            'warehouse_id' => $warehouse->id,
            'batch_id' => null,
            'batch_key' => 0,
            'qty' => 10,
            'committed_qty' => 0,
            'on_order_qty' => 0,
            'value' => 250,
        ]);

        $asset = WorkCentre::factory()->create(['status' => AssetStatus::Active]);

        $create = $this->actingAs($user)->postJson(route('admin.maintenance-orders.store'), [
            'document_date' => now()->toDateString(),
            'order_type' => MaintenanceOrderType::Preventive->value,
            'work_centre_id' => $asset->id,
            'reason' => 'Scheduled service',
            'parts' => [[
                'item_id' => $spare->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => 2,
            ]],
        ])->assertCreated();

        $orderId = $create->json('data.id');

        $this->actingAs($user)
            ->postJson(route('admin.maintenance-orders.issue-parts', $orderId))
            ->assertOk();

        $this->assertDatabaseHas('stock_ledger_entries', [
            'item_id' => $spare->id,
            'warehouse_id' => $warehouse->id,
            'transaction_type' => StockTransactionType::MaintenanceIssue->value,
            'qty_out' => 2,
        ]);

        $this->assertEqualsWithDelta(
            8.0,
            (float) StockBalance::query()->where('item_id', $spare->id)->where('warehouse_id', $warehouse->id)->value('qty'),
            0.0001
        );
    }

    public function test_pm_due_list_includes_asset_near_interval(): void
    {
        $user = User::factory()->superAdmin()->create();
        WorkCentre::factory()->create([
            'code' => 'DUE-01',
            'status' => AssetStatus::Active,
            'is_active' => true,
            'service_interval_cycles' => 100,
            'cycles_used' => 95,
            'cycles_at_last_service' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('admin.work-centres.due'))
            ->assertOk()
            ->assertSee('DUE-01');
    }
}
