<?php

namespace App\Repositories\Eloquent;

use App\Models\Item;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\ItemRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Eloquent item repository.
 */
class ItemRepository implements ItemRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): Item
    {
        return Item::query()
            ->with([
                'category:id,name',
                'subCategory:id,name',
                'stockUom:id,code,name',
                'purchaseUom:id,code,name',
                'salesUom:id,code,name',
                'hsnCode:id,code,description,default_gst_rate',
                'defaultWarehouse:id,name',
                'uomConversions',
                'warehouseSettings.warehouse:id,name',
                'substitutes.substituteItem:id,item_code,item_name',
            ])
            ->findOrFail($id);
    }

    public function create(array $data): Item
    {
        return Item::query()->create($data);
    }

    public function update(int $id, array $data): Item
    {
        $item = $this->findById($id);
        $item->update($data);

        return $item->fresh([
            'category',
            'stockUom',
            'hsnCode',
            'uomConversions',
            'warehouseSettings',
            'substitutes',
        ]);
    }

    public function delete(int $id): bool
    {
        return (bool) $this->findById($id)->delete();
    }

    public function nextItemCode(string $prefix = 'ITM'): string
    {
        $latest = Item::query()
            ->withTrashed()
            ->where('item_code', 'like', $prefix.'-%')
            ->orderByDesc('id')
            ->value('item_code');

        $sequence = 1;
        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return $prefix.'-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    public function findDuplicateName(string $name, int $categoryId, ?int $ignoreId = null): ?Item
    {
        return Item::query()
            ->where('category_id', $categoryId)
            ->whereRaw('LOWER(item_name) = ?', [mb_strtolower($name)])
            ->when($ignoreId !== null, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
            ->first();
    }

    public function getForDataTable(array $params): array
    {
        $query = Item::query()->with(['category:id,name', 'stockUom:id,code', 'hsnCode:id,code']);

        if (! empty($params['item_type'])) {
            $query->where('item_type', $params['item_type']);
        }
        if (! empty($params['category_id'])) {
            $query->where('category_id', $params['category_id']);
        }

        return $this->buildDataTable(
            $query,
            ['id', 'item_code', 'item_name', 'item_type', 'category_id', 'is_active', 'created_at'],
            ['item_code', 'item_name', 'barcode'],
            function (Item $item): array {
                return [
                    'id' => $item->id,
                    'item_code' => $item->item_code,
                    'item_name' => e($item->item_name),
                    'item_type' => $item->item_type->label(),
                    'category' => $item->category?->name ?? '—',
                    'stock_uom' => $item->stockUom?->code ?? '—',
                    'hsn' => $item->hsnCode?->code ?? '—',
                    'is_active' => $item->is_active
                        ? '<span class="badge bg-success-transparent">Active</span>'
                        : '<span class="badge bg-danger-transparent">Inactive</span>',
                    'action' => view('admin.items.partials.actions', ['item' => $item])->render(),
                ];
            },
            $params
        );
    }
}
