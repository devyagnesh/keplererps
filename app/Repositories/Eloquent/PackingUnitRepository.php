<?php

namespace App\Repositories\Eloquent;

use App\Models\PackingUnit;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\PackingUnitRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Eloquent packing unit repository.
 */
class PackingUnitRepository implements PackingUnitRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): PackingUnit
    {
        return PackingUnit::query()
            ->with(['item:id,item_code,item_name', 'uom:id,code,name', 'parent:id,code,name,quantity,parent_id'])
            ->findOrFail($id);
    }

    public function create(array $data): PackingUnit
    {
        return PackingUnit::query()->create($data);
    }

    public function update(int $id, array $data): PackingUnit
    {
        $record = PackingUnit::query()->findOrFail($id);
        $record->update($data);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        return (bool) PackingUnit::query()->findOrFail($id)->delete();
    }

    public function getForDataTable(array $params): array
    {
        $query = PackingUnit::query()->with([
            'item:id,item_code,item_name',
            'uom:id,code',
            'parent:id,name,quantity,parent_id',
        ]);

        if (! empty($params['item_id'])) {
            $query->where('item_id', (int) $params['item_id']);
        }

        return $this->buildDataTable(
            $query,
            ['id', 'code', 'name', 'quantity', 'is_active', 'created_at'],
            ['code', 'name', 'remarks'],
            function (PackingUnit $unit): array {
                return [
                    'id' => $unit->id,
                    'code' => e($unit->code),
                    'name' => e($unit->nestingPath()),
                    'item' => e($unit->item?->item_code ?? 'Generic'),
                    'quantity' => number_format((float) $unit->quantity, 4, '.', ''),
                    'base_quantity' => number_format($unit->baseQuantity(), 4, '.', '').' '.e($unit->uom?->code ?? ''),
                    'is_active' => $unit->is_active
                        ? '<span class="badge bg-success-transparent">Active</span>'
                        : '<span class="badge bg-danger-transparent">Inactive</span>',
                    'action' => view('admin.packing-units.partials.actions', ['unit' => $unit])->render(),
                ];
            },
            $params
        );
    }

    public function selectableForItem(?int $itemId = null): Collection
    {
        return PackingUnit::query()
            ->with(['parent:id,name,quantity,parent_id', 'uom:id,code'])
            ->where('is_active', true)
            ->when($itemId, fn ($q) => $q->where(function ($inner) use ($itemId): void {
                $inner->where('item_id', $itemId)->orWhereNull('item_id');
            }))
            ->orderBy('code')
            ->get();
    }
}
