<?php

namespace Database\Seeders;

use App\Models\ManufacturingOperation;
use Illuminate\Database\Seeder;

/**
 * Seeds common manufacturing operations for BOM routing (M04).
 */
class ManufacturingOperationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $operations = [
            ['code' => 'MIX', 'name' => 'Mixing', 'sort_order' => 10],
            ['code' => 'EXT', 'name' => 'Extrusion', 'sort_order' => 20],
            ['code' => 'MLD', 'name' => 'Moulding', 'sort_order' => 30],
            ['code' => 'CUT', 'name' => 'Cutting', 'sort_order' => 40],
            ['code' => 'PRT', 'name' => 'Printing', 'sort_order' => 50],
            ['code' => 'ASM', 'name' => 'Assembly', 'sort_order' => 60],
            ['code' => 'ANO', 'name' => 'Anodising', 'sort_order' => 70],
            ['code' => 'PKG', 'name' => 'Packing', 'sort_order' => 80],
        ];

        foreach ($operations as $operation) {
            ManufacturingOperation::query()->updateOrCreate(
                ['code' => $operation['code']],
                [
                    'name' => $operation['name'],
                    'sort_order' => $operation['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
