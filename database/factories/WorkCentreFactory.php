<?php

namespace Database\Factories;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\WorkCentre;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkCentre>
 */
class WorkCentreFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('WC##')),
            'name' => fake()->words(2, true).' Centre',
            'asset_type' => AssetType::Machine,
            'status' => AssetStatus::Active,
            'machine_rate_per_hour' => fake()->randomFloat(2, 50, 500),
            'labour_rate_per_hour' => fake()->randomFloat(2, 20, 200),
            'is_active' => true,
        ];
    }
}
