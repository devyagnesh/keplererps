<?php

namespace Database\Factories;

use App\Enums\EmploymentStatus;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_code' => strtoupper(fake()->unique()->bothify('EMP-####')),
            'full_name' => fake()->name(),
            'designation' => fake()->jobTitle(),
            'department' => fake()->randomElement(['Production', 'Stores', 'Quality', 'Accounts']),
            'branch_id' => null,
            'shift_id' => null,
            'user_id' => null,
            'mobile' => fake()->numerify('9#########'),
            'email' => fake()->unique()->safeEmail(),
            'date_of_joining' => now()->subYear()->toDateString(),
            'date_of_exit' => null,
            'status' => EmploymentStatus::Active,
            'monthly_gross' => 30000,
            'basic_percent' => 50,
            'fixed_deduction' => 0,
            'overtime_rate_per_hour' => 0,
        ];
    }

    /**
     * An employee who has left the company.
     */
    public function resigned(?string $exitDate = null): static
    {
        return $this->state(fn (): array => [
            'status' => EmploymentStatus::Resigned,
            'date_of_exit' => $exitDate ?? now()->subMonth()->toDateString(),
        ]);
    }
}
