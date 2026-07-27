<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Batch>
 */
class BatchFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_id' => Item::factory(),
            'batch_no' => strtoupper(fake()->unique()->bothify('B####')),
            'mfg_date' => now()->subDays(10)->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
            'supplier_batch_no' => null,
            'parent_batch_id' => null,
            'is_active' => true,
        ];
    }
}
