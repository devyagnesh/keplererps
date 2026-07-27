<?php

namespace Database\Factories;

use App\Models\Bom;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bom>
 */
class BomFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $item = Item::factory()->create(['is_manufacturable' => true]);

        return [
            'bom_number' => 'BOM-'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'item_id' => $item->id,
            'version' => 1,
            'output_quantity' => 1,
            'output_uom_id' => $item->stock_uom_id,
            'valid_from' => now()->toDateString(),
            'valid_to' => null,
            'is_active' => true,
            'overhead_percent' => 0,
            'notes' => null,
            'rolled_material_cost' => 0,
            'rolled_operation_cost' => 0,
            'rolled_total_cost' => 0,
        ];
    }
}
