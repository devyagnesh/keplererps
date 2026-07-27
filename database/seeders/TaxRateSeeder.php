<?php

namespace Database\Seeders;

use App\Models\TaxRate;
use Illuminate\Database\Seeder;

/**
 * Seeds common Indian GST slabs.
 */
class TaxRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rates = [
            ['code' => 'GST0', 'name' => 'GST 0%', 'cgst_rate' => 0, 'sgst_rate' => 0, 'igst_rate' => 0],
            ['code' => 'GST5', 'name' => 'GST 5%', 'cgst_rate' => 2.5, 'sgst_rate' => 2.5, 'igst_rate' => 5],
            ['code' => 'GST12', 'name' => 'GST 12%', 'cgst_rate' => 6, 'sgst_rate' => 6, 'igst_rate' => 12],
            ['code' => 'GST18', 'name' => 'GST 18%', 'cgst_rate' => 9, 'sgst_rate' => 9, 'igst_rate' => 18],
            ['code' => 'GST28', 'name' => 'GST 28%', 'cgst_rate' => 14, 'sgst_rate' => 14, 'igst_rate' => 28],
        ];

        foreach ($rates as $rate) {
            TaxRate::query()->updateOrCreate(
                ['code' => $rate['code']],
                array_merge($rate, ['cess_rate' => 0, 'is_active' => true])
            );
        }
    }
}
