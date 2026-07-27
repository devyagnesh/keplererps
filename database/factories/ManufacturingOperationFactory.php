<?php

namespace Database\Factories;

use App\Models\ManufacturingOperation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ManufacturingOperation>
 */
class ManufacturingOperationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = strtoupper(fake()->unique()->bothify('OP##'));

        return [
            'code' => $code,
            'name' => fake()->words(2, true),
            'description' => null,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
