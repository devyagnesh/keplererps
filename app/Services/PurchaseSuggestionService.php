<?php

namespace App\Services;

use App\Enums\PurchaseOrderStatus;
use App\Models\ItemWarehouseSetting;
use App\Models\PurchaseOrderItem;
use App\Models\StockBalance;
use Illuminate\Support\Collection;

/**
 * Purchase suggestions from reorder levels and posted production plan shortages (US-M07-01).
 */
class PurchaseSuggestionService
{
    public function __construct(protected ProductionPlanService $productionPlans) {}

    /**
     * Reorder-level suggestions merged with component shortages of posted production plans.
     *
     * @return list<array<string, mixed>>
     */
    public function suggestions(?int $warehouseId = null): array
    {
        return Collection::make($this->reorderSuggestions($warehouseId))
            ->concat($this->productionPlans->openShortages($warehouseId))
            ->sortBy(fn (array $row): string => (string) $row['item_code'])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function reorderSuggestions(?int $warehouseId = null): array
    {
        $settings = ItemWarehouseSetting::query()
            ->with(['item:id,item_code,item_name,is_purchasable,is_active,stock_uom_id,standard_cost', 'warehouse:id,code,name'])
            ->where('reorder_level', '>', 0)
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->whereHas('item', fn ($q) => $q->where('is_purchasable', true)->where('is_active', true))
            ->get();

        $rows = [];

        foreach ($settings as $setting) {
            $balance = StockBalance::query()
                ->where('item_id', $setting->item_id)
                ->where('warehouse_id', $setting->warehouse_id)
                ->selectRaw('COALESCE(SUM(qty),0) as qty, COALESCE(SUM(committed_qty),0) as committed_qty, COALESCE(SUM(on_order_qty),0) as on_order_qty')
                ->first();

            $physical = (float) ($balance->qty ?? 0);
            $committed = (float) ($balance->committed_qty ?? 0);
            $onOrder = (float) ($balance->on_order_qty ?? 0);
            $free = $physical - $committed;
            $reorderLevel = (float) $setting->reorder_level;

            if ($free > $reorderLevel) {
                continue;
            }

            $suggested = (float) ($setting->reorder_qty ?? max(0, $reorderLevel - $free - $onOrder));
            if ($suggested <= 0 && $onOrder <= 0) {
                $suggested = max(1, $reorderLevel);
            }

            $openPoQty = (float) PurchaseOrderItem::query()
                ->where('item_id', $setting->item_id)
                ->whereHas('purchaseOrder', function ($q) use ($setting): void {
                    $q->where('warehouse_id', $setting->warehouse_id)
                        ->whereIn('status', [
                            PurchaseOrderStatus::Approved->value,
                            PurchaseOrderStatus::Sent->value,
                            PurchaseOrderStatus::PartiallyReceived->value,
                        ]);
                })
                ->selectRaw('COALESCE(SUM(quantity - received_qty),0) as pending')
                ->value('pending');

            $rows[] = [
                'source' => 'reorder_level',
                'reference' => null,
                'item_id' => $setting->item_id,
                'item_code' => $setting->item?->item_code,
                'item_name' => $setting->item?->item_name,
                'warehouse_id' => $setting->warehouse_id,
                'warehouse' => $setting->warehouse?->name,
                'stock_uom_id' => $setting->item?->stock_uom_id,
                'physical_qty' => round($physical, 4),
                'committed_qty' => round($committed, 4),
                'free_qty' => round($free, 4),
                'on_order_qty' => round(max($onOrder, $openPoQty), 4),
                'reorder_level' => $reorderLevel,
                'suggested_qty' => round(max(0, $suggested), 4),
                'rate' => (float) ($setting->item?->standard_cost ?? 0),
            ];
        }

        return Collection::make($rows)->values()->all();
    }
}
