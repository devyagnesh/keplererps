<?php

namespace Database\Factories;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_no' => 'LD-'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'lead_date' => now()->toDateString(),
            'company_name' => fake()->company(),
            'contact_person' => fake()->name(),
            'mobile' => fake()->numerify('98########'),
            'email' => fake()->unique()->safeEmail(),
            'city' => fake()->city(),
            'state_id' => null,
            'industry' => 'Plastics',
            'source' => LeadSource::Referral,
            'status' => LeadStatus::New,
            'requirement' => fake()->sentence(),
            'estimated_value' => 50000,
            'next_follow_up_date' => null,
            'assigned_user_id' => null,
        ];
    }

    /**
     * A lead that has been qualified and is ready to convert.
     */
    public function qualified(): self
    {
        return $this->state(fn (): array => ['status' => LeadStatus::Qualified]);
    }
}
