<?php

namespace Database\Factories;

use App\Enums\WarehouseLevel;
use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'parent_id' => null,
            'code' => strtoupper(fake()->unique()->bothify('WH###')),
            'name' => fake()->words(2, true).' Plant',
            'level' => WarehouseLevel::Plant,
            'depth' => 1,
            'warehouse_type' => 'store',
            'is_leaf' => true,
            'allow_negative_stock' => false,
            'is_system' => false,
            'is_active' => true,
        ];
    }
}
