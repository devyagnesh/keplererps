<?php

namespace Database\Factories;

use App\Enums\DocumentSeriesType;
use App\Models\DocumentNumberSeries;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentNumberSeries>
 */
class DocumentNumberSeriesFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(DocumentSeriesType::cases());

        return [
            'document_type' => $type,
            'financial_year_id' => null,
            'branch_id' => null,
            'prefix' => $type->defaultPrefix(),
            'suffix' => null,
            'separator' => '-',
            'padding' => 5,
            'start_number' => 1,
            'next_number' => 1,
            'include_fy_code' => false,
            'reset_yearly' => true,
            'is_active' => true,
        ];
    }
}
