<?php

namespace Tests\Feature\Admin;

use App\Enums\BomIssueMethod;
use App\Enums\ItemType;
use App\Models\Bom;
use App\Models\Item;
use App\Models\ManufacturingOperation;
use App\Models\User;
use App\Models\WorkCentre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for M04 Bill of Materials.
 */
class BomTest extends TestCase
{
    use RefreshDatabase;

    public function test_bom_can_be_created_with_components_and_cost_rollup(): void
    {
        $user = User::factory()->superAdmin()->create();
        $finished = Item::factory()->create([
            'item_type' => ItemType::FinishedGoods,
            'is_manufacturable' => true,
            'standard_cost' => 0,
        ]);
        $rm = Item::factory()->create([
            'item_type' => ItemType::RawMaterial,
            'standard_cost' => 10,
        ]);
        $operation = ManufacturingOperation::factory()->create();
        $workCentre = WorkCentre::factory()->create([
            'machine_rate_per_hour' => 60,
            'labour_rate_per_hour' => 30,
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('admin.boms.store'), [
                'item_id' => $finished->id,
                'output_quantity' => 1,
                'valid_from' => now()->toDateString(),
                'is_active' => 1,
                'overhead_percent' => 10,
                'components' => [
                    [
                        'component_item_id' => $rm->id,
                        'quantity' => 2,
                        'uom_id' => $rm->stock_uom_id,
                        'wastage_percent' => 10,
                        'issue_method' => BomIssueMethod::Manual->value,
                        'is_critical' => 1,
                    ],
                ],
                'operations' => [
                    [
                        'sequence' => 10,
                        'manufacturing_operation_id' => $operation->id,
                        'work_centre_id' => $workCentre->id,
                        'setup_time_minutes' => 0,
                        'run_time_per_unit_minutes' => 60,
                        'machine_rate_per_hour' => 60,
                        'labour_rate_per_hour' => 30,
                        'operators_required' => 1,
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('status', true);

        $bomId = $response->json('data.id');
        $bom = Bom::query()->findOrFail($bomId);

        // Material: 2 * 1.10 * 10 = 22; Ops: 1h machine 60 + 1h labour 30 = 90; +10% OH = 123.2
        $this->assertEqualsWithDelta(22.0, (float) $bom->rolled_material_cost, 0.01);
        $this->assertEqualsWithDelta(90.0, (float) $bom->rolled_operation_cost, 0.01);
        $this->assertEqualsWithDelta(123.2, (float) $bom->rolled_total_cost, 0.01);
    }

    public function test_circular_bom_reference_is_rejected(): void
    {
        $user = User::factory()->superAdmin()->create();
        $itemA = Item::factory()->create(['is_manufacturable' => true]);
        $itemB = Item::factory()->create(['is_manufacturable' => true]);

        $this->actingAs($user)
            ->postJson(route('admin.boms.store'), [
                'item_id' => $itemA->id,
                'output_quantity' => 1,
                'valid_from' => now()->toDateString(),
                'is_active' => 1,
                'components' => [
                    [
                        'component_item_id' => $itemB->id,
                        'quantity' => 1,
                        'uom_id' => $itemB->stock_uom_id,
                        'issue_method' => BomIssueMethod::Manual->value,
                    ],
                ],
            ])
            ->assertCreated();

        $this->actingAs($user)
            ->postJson(route('admin.boms.store'), [
                'item_id' => $itemB->id,
                'output_quantity' => 1,
                'valid_from' => now()->toDateString(),
                'is_active' => 1,
                'components' => [
                    [
                        'component_item_id' => $itemA->id,
                        'quantity' => 1,
                        'uom_id' => $itemA->stock_uom_id,
                        'issue_method' => BomIssueMethod::Manual->value,
                    ],
                ],
            ])
            ->assertStatus(422);
    }

    public function test_explode_calculates_required_quantity_with_wastage(): void
    {
        $user = User::factory()->superAdmin()->create();
        $finished = Item::factory()->create(['is_manufacturable' => true]);
        $rm = Item::factory()->create(['standard_cost' => 5]);

        $create = $this->actingAs($user)
            ->postJson(route('admin.boms.store'), [
                'item_id' => $finished->id,
                'output_quantity' => 10,
                'valid_from' => now()->toDateString(),
                'is_active' => 1,
                'components' => [
                    [
                        'component_item_id' => $rm->id,
                        'quantity' => 20,
                        'uom_id' => $rm->stock_uom_id,
                        'wastage_percent' => 5,
                        'issue_method' => BomIssueMethod::Backflush->value,
                    ],
                ],
            ])
            ->assertCreated();

        $bomId = $create->json('data.id');

        $explode = $this->actingAs($user)
            ->postJson(route('admin.boms.explode', $bomId), ['order_quantity' => 50])
            ->assertOk()
            ->json('data');

        // (20/10)*50*(1.05) = 105
        $this->assertEqualsWithDelta(105.0, (float) $explode[0]['required_quantity'], 0.0001);
    }

    public function test_new_version_increments_and_closes_previous(): void
    {
        $user = User::factory()->superAdmin()->create();
        $finished = Item::factory()->create(['is_manufacturable' => true]);
        $rm = Item::factory()->create();

        $create = $this->actingAs($user)
            ->postJson(route('admin.boms.store'), [
                'item_id' => $finished->id,
                'output_quantity' => 1,
                'valid_from' => now()->subDays(10)->toDateString(),
                'is_active' => 1,
                'components' => [
                    [
                        'component_item_id' => $rm->id,
                        'quantity' => 1,
                        'uom_id' => $rm->stock_uom_id,
                        'issue_method' => BomIssueMethod::Manual->value,
                    ],
                ],
            ])
            ->assertCreated();

        $sourceId = $create->json('data.id');

        $this->actingAs($user)
            ->postJson(route('admin.boms.new-version', $sourceId))
            ->assertCreated()
            ->assertJsonPath('data.version', 2);

        $this->assertFalse((bool) Bom::query()->find($sourceId)->is_active);
        $this->assertTrue((bool) Bom::query()->where('item_id', $finished->id)->where('version', 2)->value('is_active'));
    }
}
