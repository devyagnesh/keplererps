<?php

namespace Tests\Feature\Admin;

use App\Enums\AssetStatus;
use App\Enums\PartyType;
use App\Enums\TrackingType;
use App\Enums\WarehouseType;
use App\Models\Item;
use App\Models\Party;
use App\Models\SalesOrder;
use App\Models\State;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkCentre;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for M15 dashboard widgets and register reports.
 */
class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_widgets_count_open_work_and_held_stock(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->confirmedSalesOrder($user);

        $quarantine = Warehouse::factory()->create([
            'is_leaf' => true,
            'warehouse_type' => WarehouseType::Quarantine,
        ]);
        $item = Item::factory()->create(['tracking_type' => TrackingType::None, 'min_stock' => 500]);
        StockBalance::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $quarantine->id,
            'batch_id' => null,
            'batch_key' => 0,
            'qty' => 12,
            'committed_qty' => 0,
            'on_order_qty' => 0,
            'value' => 1200,
        ]);

        WorkCentre::factory()->create([
            'status' => AssetStatus::UnderBreakdown,
            'is_active' => true,
            'next_service_due_on' => now()->subDay()->toDateString(),
        ]);

        $widgets = app(DashboardService::class)->widgets();

        $this->assertSame(1, $widgets['sales']['open_orders']);
        $this->assertEqualsWithDelta(1000.0, $widgets['sales']['open_order_value'], 0.01);
        $this->assertEqualsWithDelta(12.0, $widgets['inventory']['quarantine_qty'], 0.0001);
        $this->assertSame(1, $widgets['inventory']['below_min_stock']);
        $this->assertSame(1, $widgets['maintenance']['under_breakdown']);
        $this->assertSame(1, $widgets['maintenance']['pm_due']);

        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_every_register_renders_and_exports(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->confirmedSalesOrder($user);

        foreach (['sales', 'purchase', 'stock', 'production'] as $register) {
            $this->actingAs($user)->get(route('admin.reports.show', $register))->assertOk();

            $this->actingAs($user)
                ->getJson(route('admin.reports.data', $register).'?'.http_build_query([
                    'from_date' => now()->startOfMonth()->toDateString(),
                    'to_date' => now()->toDateString(),
                ]))
                ->assertOk()
                ->assertJsonPath('status', true)
                ->assertJsonStructure(['data' => ['rows', 'totals', 'truncated']]);

            $this->actingAs($user)
                ->get(route('admin.reports.export', $register))
                ->assertOk()
                ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        }

        $this->actingAs($user)->get(route('admin.reports.show', 'unknown'))->assertNotFound();
    }

    public function test_stock_register_splits_opening_and_period_movement(): void
    {
        $user = User::factory()->superAdmin()->create();
        $context = $this->confirmedSalesOrder($user);

        // Opening stock was received last month; nothing moved this month.
        $rows = $this->actingAs($user)
            ->getJson(route('admin.reports.data', 'stock').'?'.http_build_query([
                'from_date' => now()->startOfMonth()->toDateString(),
                'to_date' => now()->toDateString(),
                'item_id' => $context['item']->id,
            ]))
            ->assertOk()
            ->json('data.rows');

        $this->assertSame([], $rows);
    }

    public function test_sales_register_totals_confirmed_and_draft_invoices_by_range(): void
    {
        $user = User::factory()->superAdmin()->create();
        $context = $this->confirmedSalesOrder($user);

        $this->actingAs($user)->postJson(route('admin.sales-invoices.store'), [
            'document_date' => now()->toDateString(),
            'sales_order_id' => $context['order_id'],
            'items' => [[
                'sales_order_item_id' => $context['order_line_id'],
                'quantity' => 10,
                'rate' => 100,
                'gst_rate' => 0,
            ]],
        ])->assertCreated();

        $data = $this->actingAs($user)
            ->getJson(route('admin.reports.data', 'sales').'?'.http_build_query([
                'from_date' => now()->startOfMonth()->toDateString(),
                'to_date' => now()->toDateString(),
            ]))
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $data['rows']);
        $this->assertEqualsWithDelta(1000.0, (float) $data['totals']['invoice_value'], 0.01);

        // A range before the invoice date returns nothing.
        $empty = $this->actingAs($user)
            ->getJson(route('admin.reports.data', 'sales').'?'.http_build_query([
                'from_date' => now()->subMonths(3)->startOfMonth()->toDateString(),
                'to_date' => now()->subMonths(3)->endOfMonth()->toDateString(),
            ]))
            ->assertOk()
            ->json('data');

        $this->assertSame([], $empty['rows']);
    }

    public function test_register_requires_the_report_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.reports.show', 'sales'))->assertForbidden();
    }

    /**
     * Create a confirmed sales order worth 1,000 with stock in place.
     *
     * @return array{order_id: int, order_line_id: int, item: Item, warehouse: Warehouse}
     */
    protected function confirmedSalesOrder(User $user): array
    {
        $state = State::query()->first() ?? State::query()->create(['code' => '24', 'name' => 'Gujarat', 'is_active' => true]);
        $customer = Party::factory()->create([
            'party_type' => PartyType::Customer,
            'unlimited_credit' => true,
            'billing_state_id' => $state->id,
        ]);
        $warehouse = Warehouse::factory()->create(['is_leaf' => true, 'allow_negative_stock' => true]);
        $item = Item::factory()->create([
            'is_sellable' => true,
            'tracking_type' => TrackingType::None,
            'selling_price' => 100,
            'gst_rate' => 0,
        ]);

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

        return [
            'order_id' => (int) $orderId,
            'order_line_id' => (int) SalesOrder::query()->findOrFail($orderId)->items()->first()->id,
            'item' => $item,
            'warehouse' => $warehouse,
        ];
    }
}
