<?php

namespace Database\Seeders;

use App\Enums\WarehouseLevel;
use App\Enums\WarehouseType;
use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

/**
 * Ensures system Quarantine and Rejection warehouses exist per branch (M10).
 */
class WarehouseTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $definitions = [
            ['type' => WarehouseType::Quarantine, 'code' => 'QUAR', 'name' => 'Quarantine'],
            ['type' => WarehouseType::Rejection, 'code' => 'REJ', 'name' => 'Rejection'],
        ];

        Branch::query()->orderBy('id')->each(function (Branch $branch) use ($definitions): void {
            foreach ($definitions as $definition) {
                $code = $definition['code'].'-'.$branch->id;

                Warehouse::query()->updateOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'code' => $code,
                    ],
                    [
                        'name' => $definition['name'],
                        'level' => WarehouseLevel::Plant->value,
                        'depth' => 1,
                        'warehouse_type' => $definition['type']->value,
                        'is_leaf' => true,
                        'allow_negative_stock' => false,
                        'is_system' => true,
                        'is_active' => true,
                    ]
                );
            }
        });
    }
}
