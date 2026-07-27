<?php

namespace Database\Factories;

use App\Enums\OpportunityStage;
use App\Models\Opportunity;
use App\Models\Party;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Opportunity>
 */
class OpportunityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'opportunity_no' => 'OPP-'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'opportunity_date' => now()->toDateString(),
            'title' => 'Supply of moulded components',
            'lead_id' => null,
            'party_id' => Party::factory(),
            'stage' => OpportunityStage::Qualification,
            'expected_value' => 100000,
            'probability_percent' => 25,
            'expected_close_date' => now()->addDays(30)->toDateString(),
            'assigned_user_id' => null,
        ];
    }
}
