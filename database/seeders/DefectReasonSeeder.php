<?php

namespace Database\Seeders;

use App\Models\DefectReason;
use Illuminate\Database\Seeder;

/**
 * Seeds common production defect reasons (M09).
 */
class DefectReasonSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = [
            ['code' => 'SHORT_MOULD', 'name' => 'Short mould'],
            ['code' => 'BLACK_SPOT', 'name' => 'Black spot'],
            ['code' => 'BUBBLE', 'name' => 'Bubble'],
            ['code' => 'OFF_SHADE', 'name' => 'Off shade'],
            ['code' => 'BURR', 'name' => 'Burr'],
            ['code' => 'DIMENSION', 'name' => 'Dimension out'],
            ['code' => 'WELD', 'name' => 'Weld defect'],
            ['code' => 'OTHER', 'name' => 'Other'],
        ];

        foreach ($reasons as $index => $reason) {
            DefectReason::query()->updateOrCreate(
                ['code' => $reason['code']],
                [
                    'name' => $reason['name'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }
}
