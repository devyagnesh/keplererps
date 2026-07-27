<?php

namespace Database\Seeders;

use App\Models\Uom;
use Illuminate\Database\Seeder;

/**
 * Seeds common manufacturing units of measure.
 */
class UomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $uoms = [
            ['code' => 'NOS', 'name' => 'Numbers', 'uom_type' => 'count', 'decimal_places' => 0],
            ['code' => 'KG', 'name' => 'Kilogram', 'uom_type' => 'weight', 'decimal_places' => 3],
            ['code' => 'G', 'name' => 'Gram', 'uom_type' => 'weight', 'decimal_places' => 3],
            ['code' => 'MT', 'name' => 'Metric Ton', 'uom_type' => 'weight', 'decimal_places' => 3],
            ['code' => 'MTR', 'name' => 'Metre', 'uom_type' => 'length', 'decimal_places' => 3],
            ['code' => 'LTR', 'name' => 'Litre', 'uom_type' => 'volume', 'decimal_places' => 3],
            ['code' => 'BOX', 'name' => 'Box', 'uom_type' => 'count', 'decimal_places' => 0],
            ['code' => 'ROLL', 'name' => 'Roll', 'uom_type' => 'count', 'decimal_places' => 0],
            ['code' => 'BDL', 'name' => 'Bundle', 'uom_type' => 'count', 'decimal_places' => 0],
        ];

        foreach ($uoms as $uom) {
            Uom::query()->updateOrCreate(
                ['code' => $uom['code']],
                array_merge($uom, ['is_active' => true])
            );
        }
    }
}
