<?php

namespace Database\Seeders;

use App\Models\HsnCode;
use Illuminate\Database\Seeder;

/**
 * Seeds commonly used HSN / SAC codes for item master.
 */
class HsnCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['code' => '7208', 'code_type' => 'hsn', 'description' => 'Flat-rolled products of iron or non-alloy steel', 'default_gst_rate' => 18],
            ['code' => '8482', 'code_type' => 'hsn', 'description' => 'Ball or roller bearings', 'default_gst_rate' => 18],
            ['code' => '3901', 'code_type' => 'hsn', 'description' => 'Polymers of ethylene in primary forms', 'default_gst_rate' => 18],
            ['code' => '2710', 'code_type' => 'hsn', 'description' => 'Petroleum oils and oils obtained from bituminous minerals', 'default_gst_rate' => 18],
            ['code' => '998314', 'code_type' => 'sac', 'description' => 'Information technology design and development services', 'default_gst_rate' => 18],
            ['code' => '998599', 'code_type' => 'sac', 'description' => 'Other support services n.e.c.', 'default_gst_rate' => 18],
        ];

        foreach ($rows as $row) {
            HsnCode::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'code_type' => $row['code_type'],
                    'description' => $row['description'],
                    'default_gst_rate' => $row['default_gst_rate'],
                    'is_active' => true,
                ]
            );
        }
    }
}
