<?php

namespace Tests\Feature\Admin;

use App\Enums\BomIssueMethod;
use App\Enums\DocumentStatus;
use App\Enums\ItemType;
use App\Enums\PartyType;
use App\Enums\TrackingType;
use App\Enums\WorkOrderStatus;
use App\Models\Item;
use App\Models\Party;
use App\Models\ProductionPlan;
use App\Models\SalesOrder;
use App\Models\State;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for production planning: demand pull, draft work orders and shortages.
 */
class ProductionPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_demand_becomes_a_plan_that_generates_draft_work_orders(): void
    {
        $user = User::factory()->superAdmin()->create();
        $context = $this->demandContext($user, orderQty: 10);

        $demand = $this->actingAs($user)
            ->getJson(route('admin.production-plans.demand'))
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $demand);
        $this->assertEqualsWithDelta(10.0, (float) $demand[0]['open_quantity'], 0.0001);
        $this->assertSame($context['bom_id'], $demand[0]['bom_id']);

        $planId = (int) $this->actingAs($user)
            ->postJson(route('admin.production-plans.store'), [
                'document_date' => now()->toDateString(),
                'plan_from_date' => now()->toDateString(),
                'plan_to_date' => now()->addDays(20)->toDateString(),
                'source_warehouse_id' => $context['source']->id,
                'target_warehouse_id' => $context['target']->id,
                'items' => [[
                    'item_id' => $demand[0]['item_id'],
                    'bom_id' => $demand[0]['bom_id'],
                    'sales_order_id' => $demand[0]['sales_order_id'],
                    'sales_order_item_id' => $demand[0]['sales_order_item_id'],
                    'planned_quantity' => $demand[0]['open_quantity'],
                    'required_date' => $demand[0]['required_date'],
                ]],
            ])
            ->assertCreated()
            ->json('data.id');

        // 10 finished units × 2 components each, with 15 in stock leaves a shortage of 5.
        $this->assertDatabaseHas('production_plan_shortages', [
            'production_plan_id' => $planId,
            'item_id' => $context['component']->id,
            'shortage_quantity' => 5,
        ]);

        $this->actingAs($user)
            ->postJson(route('admin.production-plans.generate', $planId))
            ->assertOk()
            ->assertJsonPath('data.status', DocumentStatus::Posted->value);

        $plan = ProductionPlan::query()->with('items')->findOrFail($planId);
        $workOrder = WorkOrder::query()->findOrFail($plan->items->first()->work_order_id);

        $this->assertSame(WorkOrderStatus::Draft, $workOrder->status);
        $this->assertEqualsWithDelta(10.0, (float) $workOrder->planned_quantity, 0.0001);
        $this->assertSame((int) $context['sales_order_item_id'], (int) $workOrder->sales_order_item_id);
        $this->assertSame($context['source']->id, (int) $workOrder->source_warehouse_id);
    }

    public function test_planned_quantity_is_removed_from_the_open_demand(): void
    {
        $user = User::factory()->superAdmin()->create();
        $context = $this->demandContext($user, orderQty: 10);

        $planId = (int) $this->actingAs($user)
            ->postJson(route('admin.production-plans.store'), [
                'document_date' => now()->toDateString(),
                'plan_from_date' => now()->toDateString(),
                'plan_to_date' => now()->addDays(20)->toDateString(),
                'source_warehouse_id' => $context['source']->id,
                'target_warehouse_id' => $context['target']->id,
                'items' => [[
                    'item_id' => $context['finished']->id,
                    'bom_id' => $context['bom_id'],
                    'sales_order_id' => $context['sales_order_id'],
                    'sales_order_item_id' => $context['sales_order_item_id'],
                    'planned_quantity' => 4,
                ]],
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user)->postJson(route('admin.production-plans.generate', $planId))->assertOk();

        $demand = $this->actingAs($user)
            ->getJson(route('admin.production-plans.demand'))
            ->assertOk()
            ->json('data');

        $this->assertEqualsWithDelta(6.0, (float) $demand[0]['open_quantity'], 0.0001);
    }

    public function test_cancelling_a_plan_deletes_its_draft_work_orders(): void
    {
        $user = User::factory()->superAdmin()->create();
        $context = $this->demandContext($user, orderQty: 6);

        $planId = (int) $this->actingAs($user)
            ->postJson(route('admin.production-plans.store'), [
                'document_date' => now()->toDateString(),
                'plan_from_date' => now()->toDateString(),
                'plan_to_date' => now()->addDays(10)->toDateString(),
                'source_warehouse_id' => $context['source']->id,
                'target_warehouse_id' => $context['target']->id,
                'items' => [[
                    'item_id' => $context['finished']->id,
                    'bom_id' => $context['bom_id'],
                    'planned_quantity' => 6,
                ]],
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user)->postJson(route('admin.production-plans.generate', $planId))->assertOk();

        $workOrderId = (int) ProductionPlan::query()->with('items')->findOrFail($planId)->items->first()->work_order_id;

        $this->actingAs($user)
            ->postJson(route('admin.production-plans.cancel', $planId))
            ->assertOk()
            ->assertJsonPath('data.status', DocumentStatus::Cancelled->value);

        $this->assertSoftDeleted('work_orders', ['id' => $workOrderId]);
    }

    public function test_plan_shortages_appear_in_purchase_suggestions(): void
    {
        $user = User::factory()->superAdmin()->create();
        $context = $this->demandContext($user, orderQty: 10);

        $planId = (int) $this->actingAs($user)
            ->postJson(route('admin.production-plans.store'), [
                'document_date' => now()->toDateString(),
                'plan_from_date' => now()->toDateString(),
                'plan_to_date' => now()->addDays(10)->toDateString(),
                'source_warehouse_id' => $context['source']->id,
                'target_warehouse_id' => $context['target']->id,
                'items' => [[
                    'item_id' => $context['finished']->id,
                    'bom_id' => $context['bom_id'],
                    'planned_quantity' => 10,
                ]],
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user)->postJson(route('admin.production-plans.generate', $planId))->assertOk();

        $suggestions = $this->actingAs($user)
            ->getJson(route('admin.purchase-suggestions.data', ['warehouse_id' => $context['source']->id]))
            ->assertOk()
            ->json('data');

        $planRows = collect($suggestions)->where('source', 'production_plan')->values();

        $this->assertCount(1, $planRows);
        $this->assertSame($context['component']->id, $planRows[0]['item_id']);
        $this->assertEqualsWithDelta(5.0, (float) $planRows[0]['suggested_qty'], 0.0001);
    }

    public function test_production_plan_pages_render(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)->get(route('admin.production-plans.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.production-plans.create'))->assertOk();
    }

    /**
     * Confirmed sales order for a manufacturable item with an active BOM and partial component stock.
     *
     * @return array{finished: Item, component: Item, source: Warehouse, target: Warehouse, bom_id: int, sales_order_id: int, sales_order_item_id: int}
     */
    protected function demandContext(User $user, float $orderQty): array
    {
        $finished = Item::factory()->create([
            'item_type' => ItemType::FinishedGoods,
            'is_manufacturable' => true,
            'is_sellable' => true,
            'tracking_type' => TrackingType::None,
            'gst_rate' => 18,
            'selling_price' => 100,
        ]);
        $component = Item::factory()->create([
            'item_type' => ItemType::RawMaterial,
            'is_purchasable' => true,
            'tracking_type' => TrackingType::None,
            'standard_cost' => 10,
        ]);

        $source = Warehouse::factory()->create(['is_leaf' => true]);
        $target = Warehouse::factory()->create(['is_leaf' => true]);
        $salesWarehouse = Warehouse::factory()->create(['is_leaf' => true]);

        StockBalance::query()->create([
            'item_id' => $component->id,
            'warehouse_id' => $source->id,
            'batch_id' => null,
            'batch_key' => 0,
            'qty' => 15,
            'committed_qty' => 0,
            'on_order_qty' => 0,
            'value' => 150,
        ]);

        StockBalance::query()->create([
            'item_id' => $finished->id,
            'warehouse_id' => $salesWarehouse->id,
            'batch_id' => null,
            'batch_key' => 0,
            'qty' => $orderQty,
            'committed_qty' => 0,
            'on_order_qty' => 0,
            'value' => $orderQty * 100,
        ]);

        $bomId = (int) $this->actingAs($user)
            ->postJson(route('admin.boms.store'), [
                'item_id' => $finished->id,
                'output_quantity' => 1,
                'valid_from' => now()->toDateString(),
                'is_active' => true,
                'overhead_percent' => 0,
                'components' => [[
                    'component_item_id' => $component->id,
                    'quantity' => 2,
                    'uom_id' => $component->stock_uom_id,
                    'wastage_percent' => 0,
                    'is_critical' => true,
                    'issue_method' => BomIssueMethod::Backflush->value,
                ]],
                'operations' => [],
                'outputs' => [],
            ])
            ->assertCreated()
            ->json('data.id');

        $customer = Party::factory()->create(['party_type' => PartyType::Customer]);

        $orderId = (int) $this->actingAs($user)
            ->postJson(route('admin.sales-orders.store'), [
                'document_date' => now()->toDateString(),
                'customer_id' => $customer->id,
                'warehouse_id' => $salesWarehouse->id,
                'place_of_supply_state_id' => State::query()->value('id'),
                'expected_delivery_date' => now()->addDays(10)->toDateString(),
                'items' => [[
                    'item_id' => $finished->id,
                    'uom_id' => $finished->stock_uom_id,
                    'quantity' => $orderQty,
                    'rate' => 100,
                    'gst_rate' => 18,
                ]],
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user)->postJson(route('admin.sales-orders.confirm', $orderId))->assertOk();

        return [
            'finished' => $finished,
            'component' => $component,
            'source' => $source,
            'target' => $target,
            'bom_id' => $bomId,
            'sales_order_id' => $orderId,
            'sales_order_item_id' => (int) SalesOrder::query()->findOrFail($orderId)->items()->first()->id,
        ];
    }
}
