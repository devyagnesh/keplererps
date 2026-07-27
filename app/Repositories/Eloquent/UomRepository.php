<?php

namespace App\Repositories\Eloquent;

use App\Models\Uom;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\UomRepositoryInterface;

/**
 * Eloquent UOM repository.
 */
class UomRepository implements UomRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): Uom
    {
        return Uom::query()->findOrFail($id);
    }

    public function create(array $data): Uom
    {
        return Uom::query()->create($data);
    }

    public function update(int $id, array $data): Uom
    {
        $record = $this->findById($id);
        $record->update($data);

        return $record->fresh();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->findById($id)->delete();
    }

    public function getForDataTable(array $params): array
    {
        return $this->buildDataTable(
            Uom::query(),
            ['id', 'code', 'name', 'uom_type', 'is_active', 'created_at'],
            ['code', 'name'],
            function (Uom $uom): array {
                return [
                    'id' => $uom->id,
                    'code' => $uom->code,
                    'name' => e($uom->name),
                    'uom_type' => ucfirst($uom->uom_type),
                    'decimal_places' => $uom->decimal_places,
                    'is_active' => $uom->is_active
                        ? '<span class="badge bg-success-transparent">Active</span>'
                        : '<span class="badge bg-danger-transparent">Inactive</span>',
                    'action' => view('admin.uoms.partials.actions', ['uom' => $uom])->render(),
                ];
            },
            $params
        );
    }
}
