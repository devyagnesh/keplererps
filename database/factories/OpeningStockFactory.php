<?php

namespace Database\Factories;

use App\Models\OpeningStock;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OpeningStock>
 */
class OpeningStockFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_no' => 'OS-'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'document_date' => now()->toDateString(),
            'warehouse_id' => Warehouse::factory(),
            'status' => 'draft',
            'remarks' => null,
        ];
    }
}
