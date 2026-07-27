<?php

namespace Database\Factories;

use App\Models\Uom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Uom>
 */
class UomFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('U##')),
            'name' => fake()->word(),
            'uom_type' => 'count',
            'decimal_places' => 3,
            'is_active' => true,
            'has_transactions' => false,
        ];
    }
}
