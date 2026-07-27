<?php

namespace Database\Factories;

use App\Models\Transporter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transporter>
 */
class TransporterFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('TR###')),
            'name' => fake()->company().' Logistics',
            'gstin' => null,
            'phone' => '9'.fake()->numerify('#########'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->address(),
            'vehicle_types' => 'Truck, Tempo',
            'is_active' => true,
            'has_transactions' => false,
        ];
    }
}
