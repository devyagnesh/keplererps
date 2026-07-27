<?php

namespace Tests\Feature\Admin;

use App\Enums\BomIssueMethod;
use App\Enums\ItemType;
use App\Enums\TrackingType;
use App\Enums\WorkOrderStatus;
use App\Models\Item;
use App\Models\ManufacturingOperation;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkCentre;
use App\Models\WorkOrder;
use Database\Seeders\DefectReasonSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for M09 Production work order / entry flow.
 */
class ProductionTest extends TestCase
{
    use RefreshDatabase;

    public function test_work_order_release_backflush_production_and_close(): void
    {
        $user = User::factory()->superAdmin()->create();
        (new DefectReasonSeeder)->run();

        $finished = Item::factory()->create([
            'item_type' => ItemType::FinishedGoods,
            'is_manufacturable' => true,
            'is_sellable' => true,
            'tracking_type' => TrackingType::None,
            'standard_cost' => 0,
        ]);
        $rm = Item::factory()->create([
            'item_type' => ItemType::RawMaterial,
            'standard_cost' => 10,
            'tracking_type' => TrackingType::None,
        ]);
        $operation = ManufacturingOperation::factory()->create();
        $workCentre = WorkCentre::factory()->create([
            'machine_rate_per_hour' => 60,
            'labour_rate_per_hour' => 30,
        ]);
        $source = Warehouse::factory()->create(['is_leaf' => true, 'allow_negative_stock' => false]);
        $target = Warehouse::factory()->create(['is_leaf' => true, 'allow_negative_stock' => false]);

        StockBalance::query()->create([
            'item_id' => $rm->id,
            'warehouse_id' => $source->id,
            'batch_id' => null,
            'batch_key' => 0,
            'qty' => 100,
            'committed_qty' => 0,
            'on_order_qty' => 0,
            'value' => 1000,
        ]);

        $bom = $this->actingAs($user)->postJson(route('admin.boms.store'), [
            'item_id' => $finished->id,
            'output_quantity' => 1,
            'valid_from' => now()->toDateString(),
            'is_active' => true,
            'overhead_percent' => 10,
            'components' => [[
                'component_item_id' => $rm->id,
                'quantity' => 2,
                'uom_id' => $rm->stock_uom_id,
                'wastage_percent' => 0,
                'is_critical' => true,
                'issue_method' => BomIssueMethod::Backflush->value,
            ]],
            'operations' => [[
                'sequence' => 10,
                'manufacturing_operation_id' => $operation->id,
                'work_centre_id' => $workCentre->id,
                'setup_time_minutes' => 0,
                'run_time_per_unit_minutes' => 6,
                'machine_rate_per_hour' => 60,
                'labour_rate_per_hour' => 30,
                'operators_required' => 1,
            ]],
            'outputs' => [],
        ])->assertCreated();

        $bomId = $bom->json('data.id');

        $wo = $this->actingAs($user)->postJson(route('admin.work-orders.store'), [
            'document_date' => now()->toDateString(),
            'item_id' => $finished->id,
            'bom_id' => $bomId,
            'planned_quantity' => 10,
            'planned_start_date' => now()->toDateString(),
            'planned_end_date' => now()->addDays(2)->toDateString(),
            'source_warehouse_id' => $source->id,
            'target_warehouse_id' => $target->id,
            'work_centre_id' => $workCentre->id,
            'priority' => 'normal',
        ])->assertCreated();

        $woId = $wo->json('data.id');

        $this->actingAs($user)
            ->postJson(route('admin.work-orders.release', $woId))
            ->assertOk()
            ->assertJsonPath('data.status', WorkOrderStatus::Released->value);

        $this->assertDatabaseHas('stock_balances', [
            'item_id' => $rm->id,
            'warehouse_id' => $source->id,
            'committed_qty' => 20,
        ]);

        $entry = $this->actingAs($user)->postJson(route('admin.production-entries.store'), [
            'work_order_id' => $woId,
            'document_date' => now()->toDateString(),
            'good_quantity' => 10,
            'rejected_quantity' => 0,
            'post_immediately' => true,
        ])->assertCreated();

        $this->assertNotNull($entry->json('data.posted_at'));

        $this->assertDatabaseHas('stock_balances', [
            'item_id' => $rm->id,
            'warehouse_id' => $source->id,
            'qty' => 80,
        ]);

        $this->assertDatabaseHas('stock_balances', [
            'item_id' => $finished->id,
            'warehouse_id' => $target->id,
            'qty' => 10,
        ]);

        $this->actingAs($user)
            ->postJson(route('admin.work-orders.close', $woId))
            ->assertOk()
            ->assertJsonPath('data.status', WorkOrderStatus::Closed->value);

        $closed = WorkOrder::query()->findOrFail($woId);
        $this->assertGreaterThan(0, (float) $closed->actual_total_cost);
        $this->assertEqualsWithDelta(10.0, (float) $closed->good_quantity, 0.0001);
    }

    public function test_critical_shortage_blocks_release(): void
    {
        $user = User::factory()->superAdmin()->create();
        $finished = Item::factory()->create([
            'item_type' => ItemType::FinishedGoods,
            'is_manufacturable' => true,
        ]);
        $rm = Item::factory()->create([
            'item_type' => ItemType::RawMaterial,
            'standard_cost' => 5,
        ]);
        $operation = ManufacturingOperation::factory()->create();
        $workCentre = WorkCentre::factory()->create();
        $source = Warehouse::factory()->create(['is_leaf' => true]);
        $target = Warehouse::factory()->create(['is_leaf' => true]);

        $bomId = $this->actingAs($user)->postJson(route('admin.boms.store'), [
            'item_id' => $finished->id,
            'output_quantity' => 1,
            'valid_from' => now()->toDateString(),
            'is_active' => true,
            'overhead_percent' => 0,
            'components' => [[
                'component_item_id' => $rm->id,
                'quantity' => 5,
                'uom_id' => $rm->stock_uom_id,
                'wastage_percent' => 0,
                'is_critical' => true,
                'issue_method' => BomIssueMethod::Manual->value,
            ]],
            'operations' => [[
                'sequence' => 10,
                'manufacturing_operation_id' => $operation->id,
                'work_centre_id' => $workCentre->id,
                'setup_time_minutes' => 0,
                'run_time_per_unit_minutes' => 1,
                'machine_rate_per_hour' => 10,
                'labour_rate_per_hour' => 10,
                'operators_required' => 1,
            ]],
            'outputs' => [],
        ])->json('data.id');

        $woId = $this->actingAs($user)->postJson(route('admin.work-orders.store'), [
            'document_date' => now()->toDateString(),
            'item_id' => $finished->id,
            'bom_id' => $bomId,
            'planned_quantity' => 2,
            'planned_start_date' => now()->toDateString(),
            'planned_end_date' => now()->addDay()->toDateString(),
            'source_warehouse_id' => $source->id,
            'target_warehouse_id' => $target->id,
        ])->json('data.id');

        $this->actingAs($user)
            ->postJson(route('admin.work-orders.release', $woId))
            ->assertStatus(422);
    }
}
