<?php

namespace Database\Factories;

use App\Enums\GstType;
use App\Enums\PartyStatus;
use App\Enums\PartyType;
use App\Models\Party;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Party>
 */
class PartyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $state = State::query()->first() ?? State::query()->create([
            'code' => '24',
            'name' => 'Gujarat',
            'is_active' => true,
        ]);

        return [
            'party_code' => 'PTY-'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'party_name' => fake()->company(),
            'party_type' => PartyType::Customer,
            'gst_type' => GstType::Unregistered,
            'gstin' => null,
            'pan' => null,
            'billing_line1' => fake()->streetAddress(),
            'billing_line2' => null,
            'billing_city' => fake()->city(),
            'billing_state_id' => $state->id,
            'billing_pin_code' => '380015',
            'billing_country' => 'India',
            'credit_limit' => 0,
            'unlimited_credit' => false,
            'credit_days' => 30,
            'status' => PartyStatus::Active,
            'has_transactions' => false,
        ];
    }
}
