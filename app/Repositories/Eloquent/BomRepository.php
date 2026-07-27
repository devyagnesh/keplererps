<?php

namespace App\Repositories\Eloquent;

use App\Models\Bom;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\BomRepositoryInterface;

/**
 * Eloquent BOM repository.
 */
class BomRepository implements BomRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): Bom
    {
        return Bom::query()
            ->with([
                'item:id,item_code,item_name,stock_uom_id,standard_cost,is_manufacturable',
                'outputUom:id,code,name',
                'components.componentItem:id,item_code,item_name,standard_cost,stock_uom_id',
                'components.uom:id,code,name',
                'operations.manufacturingOperation:id,code,name',
                'operations.workCentre:id,code,name,machine_rate_per_hour,labour_rate_per_hour',
                'operations.vendor:id,party_code,legal_name',
                'outputs.item:id,item_code,item_name',
                'outputs.uom:id,code,name',
            ])
            ->findOrFail($id);
    }

    public function create(array $data): Bom
    {
        return Bom::query()->create($data);
    }

    public function update(int $id, array $data): Bom
    {
        $record = Bom::query()->findOrFail($id);
        $record->update($data);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        return (bool) Bom::query()->findOrFail($id)->delete();
    }

    public function nextVersionForItem(int $itemId): int
    {
        $max = (int) Bom::query()->withTrashed()->where('item_id', $itemId)->max('version');

        return $max + 1;
    }

    public function getForDataTable(array $params): array
    {
        $query = Bom::query()->with(['item:id,item_code,item_name', 'outputUom:id,code']);

        if (! empty($params['item_id'])) {
            $query->where('item_id', (int) $params['item_id']);
        }

        return $this->buildDataTable(
            $query,
            ['id', 'bom_number', 'item_id', 'version', 'valid_from', 'is_active', 'rolled_total_cost', 'created_at'],
            ['bom_number'],
            function (Bom $bom): array {
                return [
                    'id' => $bom->id,
                    'bom_number' => $bom->bom_number,
                    'item' => $bom->item
                        ? $bom->item->item_code.' — '.$bom->item->item_name
                        : '—',
                    'version' => 'v'.$bom->version,
                    'valid_from' => $bom->valid_from?->format('Y-m-d'),
                    'is_active' => $bom->is_active ? 'Active' : 'Inactive',
                    'rolled_total_cost' => number_format((float) $bom->rolled_total_cost, 2),
                    'action' => view('admin.boms.partials.actions', ['bom' => $bom])->render(),
                ];
            },
            $params
        );
    }
}
