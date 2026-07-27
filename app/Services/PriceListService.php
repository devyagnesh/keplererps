<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Party;
use App\Models\PriceList;
use App\Models\PriceListItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Customer / default selling price lists (US-M06-01).
 */
class PriceListService
{
    /**
     * @return \Illuminate\Support\Collection<int, PriceList>
     */
    public function all()
    {
        return PriceList::query()->withCount('items')->orderByDesc('is_default')->orderBy('name')->get();
    }

    public function find(int $id): PriceList
    {
        return PriceList::query()->with(['items.item:id,item_code,item_name', 'parties:id,party_code,party_name'])->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PriceList
    {
        return DB::transaction(function () use ($data): PriceList {
            if (! empty($data['is_default'])) {
                PriceList::query()->update(['is_default' => false]);
            }

            $list = PriceList::query()->create([
                'code' => strtoupper((string) $data['code']),
                'name' => $data['name'],
                'valid_from' => $data['valid_from'] ?? null,
                'valid_to' => $data['valid_to'] ?? null,
                'is_default' => (bool) ($data['is_default'] ?? false),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $this->syncItems($list, $data['items'] ?? []);
            $this->syncParties($list, $data['party_ids'] ?? []);

            return $this->find($list->id);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): PriceList
    {
        return DB::transaction(function () use ($id, $data): PriceList {
            $list = PriceList::query()->findOrFail($id);

            if (! empty($data['is_default'])) {
                PriceList::query()->where('id', '!=', $id)->update(['is_default' => false]);
            }

            $list->update([
                'code' => strtoupper((string) ($data['code'] ?? $list->code)),
                'name' => $data['name'] ?? $list->name,
                'valid_from' => $data['valid_from'] ?? $list->valid_from,
                'valid_to' => $data['valid_to'] ?? $list->valid_to,
                'is_default' => (bool) ($data['is_default'] ?? $list->is_default),
                'is_active' => (bool) ($data['is_active'] ?? $list->is_active),
            ]);

            if (array_key_exists('items', $data)) {
                $this->syncItems($list, $data['items']);
            }
            if (array_key_exists('party_ids', $data)) {
                $this->syncParties($list, $data['party_ids']);
            }

            return $this->find($id);
        });
    }

    public function delete(int $id): bool
    {
        return (bool) PriceList::query()->findOrFail($id)->delete();
    }

    /**
     * Resolve selling rate for a party + item + qty.
     */
    public function resolveRate(?int $partyId, int $itemId, float $qty = 1.0): float
    {
        $asOn = now()->toDateString();
        $listIds = [];

        if ($partyId) {
            $listIds = DB::table('party_price_lists')
                ->where('party_id', $partyId)
                ->orderBy('priority')
                ->pluck('price_list_id')
                ->all();
        }

        $defaultId = PriceList::query()
            ->where('is_default', true)
            ->where('is_active', true)
            ->value('id');

        if ($defaultId) {
            $listIds[] = (int) $defaultId;
        }

        foreach (array_unique($listIds) as $listId) {
            $list = PriceList::query()->find($listId);
            if ($list === null || ! $list->is_active) {
                continue;
            }
            if ($list->valid_from && $list->valid_from->toDateString() > $asOn) {
                continue;
            }
            if ($list->valid_to && $list->valid_to->toDateString() < $asOn) {
                continue;
            }

            $row = PriceListItem::query()
                ->where('price_list_id', $listId)
                ->where('item_id', $itemId)
                ->where('min_qty', '<=', $qty)
                ->orderByDesc('min_qty')
                ->first();

            if ($row !== null) {
                return (float) $row->rate;
            }
        }

        return (float) (Item::query()->whereKey($itemId)->value('selling_price') ?? 0);
    }

    /**
     * @param  list<array{item_id: int, min_qty?: float, rate: float}>  $items
     */
    protected function syncItems(PriceList $list, array $items): void
    {
        $list->items()->delete();

        foreach ($items as $item) {
            if (empty($item['item_id'])) {
                continue;
            }

            PriceListItem::query()->create([
                'price_list_id' => $list->id,
                'item_id' => (int) $item['item_id'],
                'min_qty' => round((float) ($item['min_qty'] ?? 1), 4),
                'rate' => round((float) $item['rate'], 4),
            ]);
        }
    }

    /**
     * @param  list<int>  $partyIds
     */
    protected function syncParties(PriceList $list, array $partyIds): void
    {
        $sync = [];
        foreach (array_values(array_unique(array_map('intval', $partyIds))) as $index => $partyId) {
            if ($partyId > 0 && Party::query()->whereKey($partyId)->exists()) {
                $sync[$partyId] = ['priority' => $index + 1];
            }
        }

        $list->parties()->sync($sync);
    }
}
