<?php

namespace Database\Factories;

use App\Enums\ItemType;
use App\Enums\TrackingType;
use App\Models\Category;
use App\Models\HsnCode;
use App\Models\Item;
use App\Models\Uom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_code' => 'ITM-'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'item_name' => fake()->words(3, true),
            'item_type' => ItemType::RawMaterial,
            'category_id' => Category::factory(),
            'sub_category_id' => null,
            'stock_uom_id' => Uom::factory(),
            'purchase_uom_id' => null,
            'sales_uom_id' => null,
            'hsn_code_id' => HsnCode::factory(),
            'gst_rate' => 18,
            'cess_rate' => 0,
            'tracking_type' => TrackingType::None,
            'expiry_tracking' => false,
            'shelf_life_days' => null,
            'standard_cost' => 100,
            'selling_price' => null,
            'minimum_selling_price' => null,
            'min_stock' => null,
            'max_stock' => null,
            'lead_time_days' => null,
            'default_warehouse_id' => null,
            'weight_per_unit' => null,
            'barcode' => null,
            'is_purchasable' => true,
            'is_sellable' => false,
            'is_manufacturable' => false,
            'requires_inspection' => false,
            'is_active' => true,
            'has_transactions' => false,
            'has_stock' => false,
            'description' => null,
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Item $item): void {
            if ($item->purchase_uom_id === null) {
                $item->purchase_uom_id = $item->stock_uom_id;
            }
            if ($item->sales_uom_id === null) {
                $item->sales_uom_id = $item->stock_uom_id;
            }
        })->afterCreating(function (Item $item): void {
            if ($item->purchase_uom_id === null || $item->sales_uom_id === null) {
                $item->forceFill([
                    'purchase_uom_id' => $item->purchase_uom_id ?? $item->stock_uom_id,
                    'sales_uom_id' => $item->sales_uom_id ?? $item->stock_uom_id,
                ])->save();
            }
        });
    }
}
