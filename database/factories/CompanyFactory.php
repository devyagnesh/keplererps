<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
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
            'legal_name' => fake()->company(),
            'trade_name' => fake()->company(),
            'is_gst_registered' => false,
            'gstin' => null,
            'pan' => 'ABCDE1234F',
            'cin' => null,
            'registered_address' => fake()->address(),
            'state_id' => $state->id,
            'pin_code' => '380015',
            'phone' => '9876543210',
            'email' => fake()->unique()->safeEmail(),
            'fy_start_month' => 4,
            'fy_start_day' => 1,
            'base_currency' => 'INR',
            'amount_decimals' => 2,
            'quantity_decimals' => 3,
            'has_transactions' => false,
        ];
    }
}
