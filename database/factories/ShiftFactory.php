<?php

namespace Database\Factories;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('SH#')),
            'name' => fake()->randomElement(['General', 'Morning', 'Evening', 'Night']).' Shift',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'break_minutes' => 60,
            'is_active' => true,
        ];
    }
}
