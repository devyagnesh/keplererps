<?php

namespace Database\Factories;

use App\Enums\InspectionType;
use App\Enums\SamplingPlanType;
use App\Models\QcTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QcTemplate>
 */
class QcTemplateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('QCT###')),
            'name' => fake()->words(3, true).' QC',
            'inspection_type' => InspectionType::Incoming,
            'item_id' => null,
            'category_id' => null,
            'sampling_plan' => SamplingPlanType::SqrtPlusOne,
            'sampling_value' => null,
            'is_active' => true,
            'notes' => null,
        ];
    }
}
