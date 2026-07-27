<?php

namespace Database\Factories;

use App\Enums\MaintenanceOrderStatus;
use App\Enums\MaintenanceOrderType;
use App\Models\MaintenanceOrder;
use App\Models\WorkCentre;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceOrder>
 */
class MaintenanceOrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_no' => 'MO-'.fake()->unique()->numerify('#####'),
            'document_date' => now()->toDateString(),
            'order_type' => MaintenanceOrderType::Breakdown,
            'status' => MaintenanceOrderStatus::Open,
            'work_centre_id' => WorkCentre::factory(),
            'opened_at' => now(),
            'reason' => fake()->sentence(),
        ];
    }
}
