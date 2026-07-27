<?php

namespace App\Services;

use App\Enums\DocumentSeriesType;
use App\Enums\ItemType;
use App\Enums\TrackingType;
use App\Models\Item;
use App\Models\ItemSubstitute;
use App\Models\ItemUomConversion;
use App\Models\ItemWarehouseSetting;
use App\Repositories\Interfaces\ItemRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Item master business logic (M03).
 */
class ItemService
{
    public function __construct(
        protected ItemRepositoryInterface $repository,
        protected NumberingService $numbering
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): Item
    {
        return $this->repository->findById($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Item
    {
        return DB::transaction(function () use ($data): Item {
            $conversions = $data['uom_conversions'] ?? [];
            $warehouseSettings = $data['warehouse_settings'] ?? [];
            $substitutes = $data['substitutes'] ?? [];
            unset($data['uom_conversions'], $data['warehouse_settings'], $data['substitutes']);

            $data = $this->normalizePayload($data);
            $data['item_code'] = $this->numbering->next(DocumentSeriesType::Item);

            $item = $this->repository->create($data);
            $this->syncUomConversions($item, $conversions);
            $this->syncWarehouseSettings($item, $warehouseSettings);
            $this->syncSubstitutes($item, $substitutes);

            $duplicate = $this->repository->findDuplicateName($item->item_name, (int) $item->category_id, $item->id);
            if ($duplicate !== null) {
                $item->setAttribute('duplicate_warning', $duplicate->item_code);
            }

            return $item->fresh(['category', 'stockUom', 'hsnCode', 'uomConversions', 'warehouseSettings', 'substitutes']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Item
    {
        return DB::transaction(function () use ($id, $data): Item {
            $item = $this->repository->findById($id);
            $conversions = $data['uom_conversions'] ?? [];
            $warehouseSettings = $data['warehouse_settings'] ?? [];
            $substitutes = $data['substitutes'] ?? [];
            unset($data['uom_conversions'], $data['warehouse_settings'], $data['substitutes']);

            if ($item->has_transactions) {
                unset($data['item_code']);
            }

            if ($item->has_stock) {
                unset($data['item_type'], $data['stock_uom_id'], $data['tracking_type']);
            }

            $data = $this->normalizePayload($data, $item);
            $updated = $this->repository->update($id, $data);
            $this->syncUomConversions($updated, $conversions);
            $this->syncWarehouseSettings($updated, $warehouseSettings);
            $this->syncSubstitutes($updated, $substitutes);

            return $updated->fresh(['category', 'stockUom', 'hsnCode', 'uomConversions', 'warehouseSettings', 'substitutes']);
        });
    }

    public function delete(int $id): bool
    {
        $item = $this->repository->findById($id);

        if ($item->has_transactions || $item->has_stock) {
            throw ValidationException::withMessages([
                'item' => 'This item has stock or transactions and cannot be deleted. Set status to Inactive instead.',
            ]);
        }

        return $this->repository->delete($id);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizePayload(array $data, ?Item $existing = null): array
    {
        $itemType = ItemType::from((string) $data['item_type']);
        $tracking = TrackingType::from((string) ($data['tracking_type'] ?? TrackingType::None->value));

        if (! $itemType->isStocked()) {
            $data['tracking_type'] = TrackingType::None->value;
            $data['expiry_tracking'] = false;
            $data['shelf_life_days'] = null;
            $data['is_manufacturable'] = false;
        } else {
            $data['tracking_type'] = $tracking->value;
        }

        if (empty($data['expiry_tracking'])) {
            $data['shelf_life_days'] = null;
        }

        if (empty($data['purchase_uom_id'])) {
            $data['purchase_uom_id'] = $data['stock_uom_id'] ?? $existing?->stock_uom_id;
        }
        if (empty($data['sales_uom_id'])) {
            $data['sales_uom_id'] = $data['stock_uom_id'] ?? $existing?->stock_uom_id;
        }

        $data['is_purchasable'] = (bool) ($data['is_purchasable'] ?? false);
        $data['is_sellable'] = (bool) ($data['is_sellable'] ?? false);
        $data['is_manufacturable'] = (bool) ($data['is_manufacturable'] ?? false);
        $data['requires_inspection'] = (bool) ($data['requires_inspection'] ?? false);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        $data['expiry_tracking'] = (bool) ($data['expiry_tracking'] ?? false);
        $data['cess_rate'] = $data['cess_rate'] ?? 0;
        $data['standard_cost'] = $data['standard_cost'] ?? 0;

        if (! empty($data['barcode'])) {
            $data['barcode'] = trim((string) $data['barcode']);
        } else {
            $data['barcode'] = null;
        }

        return $data;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    protected function syncUomConversions(Item $item, array $rows): void
    {
        $item->uomConversions()->delete();

        foreach ($rows as $row) {
            if (empty($row['from_uom_id']) || empty($row['to_uom_id']) || empty($row['factor'])) {
                continue;
            }
            if ((int) $row['from_uom_id'] === (int) $row['to_uom_id']) {
                continue;
            }

            ItemUomConversion::query()->create([
                'item_id' => $item->id,
                'from_uom_id' => (int) $row['from_uom_id'],
                'to_uom_id' => (int) $row['to_uom_id'],
                'factor' => $row['factor'],
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    protected function syncWarehouseSettings(Item $item, array $rows): void
    {
        $item->warehouseSettings()->delete();

        foreach ($rows as $row) {
            if (empty($row['warehouse_id'])) {
                continue;
            }

            ItemWarehouseSetting::query()->create([
                'item_id' => $item->id,
                'warehouse_id' => (int) $row['warehouse_id'],
                'reorder_level' => $row['reorder_level'] ?? 0,
                'reorder_qty' => $row['reorder_qty'] ?? null,
                'min_stock' => $row['min_stock'] ?? null,
                'max_stock' => $row['max_stock'] ?? null,
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    protected function syncSubstitutes(Item $item, array $rows): void
    {
        $item->substitutes()->delete();

        foreach ($rows as $row) {
            if (empty($row['substitute_item_id'])) {
                continue;
            }
            if ((int) $row['substitute_item_id'] === (int) $item->id) {
                continue;
            }

            ItemSubstitute::query()->create([
                'item_id' => $item->id,
                'substitute_item_id' => (int) $row['substitute_item_id'],
                'conversion_ratio' => $row['conversion_ratio'] ?? 1,
                'is_active' => (bool) ($row['is_active'] ?? true),
            ]);
        }
    }
}
