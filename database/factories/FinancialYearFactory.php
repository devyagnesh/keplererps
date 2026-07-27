<?php

namespace Database\Factories;

use App\Models\FinancialYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialYear>
 */
class FinancialYearFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startYear = fake()->unique()->numberBetween(2020, 2035);

        return [
            'code' => $startYear.'-'.substr((string) ($startYear + 1), -2),
            'name' => 'FY '.$startYear.'-'.($startYear + 1),
            'starts_on' => sprintf('%d-04-01', $startYear),
            'ends_on' => sprintf('%d-03-31', $startYear + 1),
            'is_current' => false,
            'is_closed' => false,
        ];
    }
}
