<?php

namespace Tests\Feature\Admin;

use App\Enums\ItemType;
use App\Enums\TrackingType;
use App\Models\Category;
use App\Models\HsnCode;
use App\Models\Item;
use App\Models\Uom;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for item master (M03).
 */
class ItemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    protected function validItemPayload(array $overrides = []): array
    {
        $category = Category::factory()->create();
        $uom = Uom::factory()->create();
        $hsn = HsnCode::factory()->create(['code' => '72081000']);

        return array_merge([
            'item_name' => 'Mild Steel Coil',
            'item_type' => ItemType::RawMaterial->value,
            'category_id' => $category->id,
            'stock_uom_id' => $uom->id,
            'purchase_uom_id' => $uom->id,
            'sales_uom_id' => $uom->id,
            'hsn_code_id' => $hsn->id,
            'gst_rate' => 18,
            'cess_rate' => 0,
            'tracking_type' => TrackingType::Batch->value,
            'expiry_tracking' => 0,
            'standard_cost' => 120.5,
            'is_purchasable' => 1,
            'is_sellable' => 0,
            'is_manufacturable' => 0,
            'requires_inspection' => 0,
            'is_active' => 1,
        ], $overrides);
    }

    public function test_item_can_be_created_with_warehouse_reorder_and_uom_conversion(): void
    {
        $user = User::factory()->superAdmin()->create();
        $purchaseUom = Uom::factory()->create(['code' => 'BOX']);
        $warehouse = Warehouse::factory()->create();
        $payload = $this->validItemPayload([
            'purchase_uom_id' => $purchaseUom->id,
            'uom_conversions' => [
                [
                    'from_uom_id' => $purchaseUom->id,
                    'to_uom_id' => null,
                    'factor' => 12,
                ],
            ],
            'warehouse_settings' => [
                [
                    'warehouse_id' => $warehouse->id,
                    'reorder_level' => 50,
                    'reorder_qty' => 100,
                    'min_stock' => 20,
                    'max_stock' => 200,
                ],
            ],
        ]);
        $payload['uom_conversions'][0]['to_uom_id'] = $payload['stock_uom_id'];

        $this->actingAs($user)
            ->postJson(route('admin.items.store'), $payload)
            ->assertCreated()
            ->assertJsonPath('status', true);

        $this->assertDatabaseHas('items', [
            'item_name' => 'Mild Steel Coil',
            'item_type' => ItemType::RawMaterial->value,
            'tracking_type' => TrackingType::Batch->value,
        ]);
        $this->assertDatabaseHas('item_uom_conversions', [
            'from_uom_id' => $purchaseUom->id,
            'factor' => 12,
        ]);
        $this->assertDatabaseHas('item_warehouse_settings', [
            'warehouse_id' => $warehouse->id,
            'reorder_level' => 50,
        ]);
    }

    public function test_item_code_is_immutable_after_transactions(): void
    {
        $user = User::factory()->superAdmin()->create();
        $item = Item::factory()->create([
            'item_code' => 'ITM-00001',
            'has_transactions' => true,
        ]);

        $this->actingAs($user)
            ->putJson(route('admin.items.update', $item), $this->validItemPayload([
                'item_code' => 'ITM-99999',
                'item_name' => $item->item_name,
                'category_id' => $item->category_id,
                'stock_uom_id' => $item->stock_uom_id,
                'purchase_uom_id' => $item->stock_uom_id,
                'sales_uom_id' => $item->stock_uom_id,
                'hsn_code_id' => $item->hsn_code_id,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['item_code']);
    }

    public function test_tracking_type_is_locked_after_stock_exists(): void
    {
        $user = User::factory()->superAdmin()->create();
        $item = Item::factory()->create([
            'tracking_type' => TrackingType::Batch,
            'has_stock' => true,
        ]);

        $this->actingAs($user)
            ->putJson(route('admin.items.update', $item), $this->validItemPayload([
                'item_code' => $item->item_code,
                'item_name' => $item->item_name,
                'category_id' => $item->category_id,
                'stock_uom_id' => $item->stock_uom_id,
                'purchase_uom_id' => $item->stock_uom_id,
                'sales_uom_id' => $item->stock_uom_id,
                'hsn_code_id' => $item->hsn_code_id,
                'tracking_type' => TrackingType::Serial->value,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tracking_type']);
    }

    public function test_item_with_stock_cannot_be_deleted(): void
    {
        $user = User::factory()->superAdmin()->create();
        $item = Item::factory()->create(['has_stock' => true]);

        $this->actingAs($user)
            ->deleteJson(route('admin.items.destroy', $item))
            ->assertStatus(422);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'deleted_at' => null,
        ]);
    }

    public function test_hsn_code_can_be_created(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->postJson(route('admin.hsn-codes.store'), [
                'code' => '84821000',
                'code_type' => 'hsn',
                'description' => 'Ball bearings',
                'default_gst_rate' => 18,
                'is_active' => 1,
            ])
            ->assertCreated()
            ->assertJsonPath('status', true);

        $this->assertDatabaseHas('hsn_codes', ['code' => '84821000']);
    }

    public function test_hsn_code_in_use_cannot_be_deleted(): void
    {
        $user = User::factory()->superAdmin()->create();
        $item = Item::factory()->create();

        $this->actingAs($user)
            ->deleteJson(route('admin.hsn-codes.destroy', $item->hsn_code_id))
            ->assertStatus(422);
    }
}
