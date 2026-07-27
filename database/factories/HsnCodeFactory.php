<?php

namespace Database\Factories;

use App\Models\HsnCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HsnCode>
 */
class HsnCodeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => (string) fake()->unique()->numerify('########'),
            'code_type' => 'hsn',
            'description' => fake()->sentence(3),
            'default_gst_rate' => 18,
            'is_active' => true,
        ];
    }
}
