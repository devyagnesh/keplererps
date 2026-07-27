<?php

namespace Database\Factories;

use App\Models\StockAdjustment;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockAdjustment>
 */
class StockAdjustmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_no' => 'ADJ-'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'document_date' => now()->toDateString(),
            'warehouse_id' => Warehouse::factory(),
            'reason' => 'Physical count variance',
            'status' => 'draft',
            'remarks' => null,
        ];
    }
}
