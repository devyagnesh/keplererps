<?php

namespace App\Services;

use App\Enums\WarehouseLevel;
use App\Enums\WarehouseType;
use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Validation\ValidationException;

/**
 * Resolves system warehouses used by QC stock moves (M10).
 */
class WarehouseResolver
{
    public function quarantineWarehouse(?int $branchId = null): Warehouse
    {
        return $this->systemWarehouse(WarehouseType::Quarantine, 'QUAR', 'Quarantine', $branchId);
    }

    public function rejectionWarehouse(?int $branchId = null): Warehouse
    {
        return $this->systemWarehouse(WarehouseType::Rejection, 'REJ', 'Rejection', $branchId);
    }

    protected function systemWarehouse(WarehouseType $type, string $code, string $name, ?int $branchId): Warehouse
    {
        $query = Warehouse::query()
            ->where('warehouse_type', $type->value)
            ->where('is_active', true)
            ->where('is_leaf', true);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $warehouse = $query->orderByDesc('is_system')->first();

        if ($warehouse !== null) {
            return $warehouse;
        }

        $branchId ??= Branch::query()->value('id');
        if ($branchId === null) {
            throw ValidationException::withMessages([
                'warehouse' => "No {$name} warehouse configured and no branch exists.",
            ]);
        }

        return Warehouse::query()->create([
            'branch_id' => $branchId,
            'code' => $code,
            'name' => $name,
            'level' => WarehouseLevel::Plant->value,
            'depth' => 1,
            'warehouse_type' => $type->value,
            'is_leaf' => true,
            'allow_negative_stock' => false,
            'is_system' => true,
            'is_active' => true,
        ]);
    }
}
