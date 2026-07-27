<?php

namespace Database\Seeders;

use App\Enums\DocumentSeriesType;
use App\Models\DocumentNumberSeries;
use App\Models\FinancialYear;
use Illuminate\Database\Seeder;

/**
 * Seeds current financial year and default document number series.
 */
class DocumentNumberSeriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $year = (int) now()->year;
        $fyStartYear = now()->month >= 4 ? $year : $year - 1;
        $code = $fyStartYear.'-'.substr((string) ($fyStartYear + 1), -2);

        $fy = FinancialYear::query()->updateOrCreate(
            ['code' => $code],
            [
                'name' => 'Financial Year '.$code,
                'starts_on' => sprintf('%d-04-01', $fyStartYear),
                'ends_on' => sprintf('%d-03-31', $fyStartYear + 1),
                'is_current' => true,
                'is_closed' => false,
            ]
        );

        FinancialYear::query()->where('id', '!=', $fy->id)->update(['is_current' => false]);

        foreach (DocumentSeriesType::cases() as $type) {
            DocumentNumberSeries::query()->updateOrCreate(
                [
                    'document_type' => $type->value,
                    'financial_year_id' => null,
                    'branch_id' => null,
                ],
                [
                    'prefix' => $type->defaultPrefix(),
                    'suffix' => null,
                    'separator' => '-',
                    'padding' => 5,
                    'start_number' => 1,
                    'next_number' => 1,
                    'include_fy_code' => false,
                    'reset_yearly' => true,
                    'is_active' => true,
                ]
            );
        }
    }
}
