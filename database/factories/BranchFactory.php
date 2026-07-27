<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $state = State::query()->first();

        return [
            'code' => strtoupper(fake()->unique()->bothify('BR###')),
            'name' => fake()->city().' Branch',
            'address' => fake()->address(),
            'state_id' => $state?->id,
            'pin_code' => '380015',
            'phone' => '9876543210',
            'email' => fake()->unique()->safeEmail(),
            'is_head_office' => false,
            'is_active' => true,
        ];
    }
}
