<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemUomConversion;
use Illuminate\Validation\ValidationException;

/**
 * Converts quantities between item UOMs using item-specific factors (SRS §4.4).
 */
class UomConversionService
{
    /**
     * Convert qty from one UOM to another for an item.
     */
    public function convert(int $itemId, float $qty, int $fromUomId, int $toUomId): float
    {
        if ($fromUomId === $toUomId) {
            return round($qty, 4);
        }

        $direct = ItemUomConversion::query()
            ->where('item_id', $itemId)
            ->where('from_uom_id', $fromUomId)
            ->where('to_uom_id', $toUomId)
            ->value('factor');

        if ($direct !== null) {
            return round($qty * (float) $direct, 4);
        }

        $inverse = ItemUomConversion::query()
            ->where('item_id', $itemId)
            ->where('from_uom_id', $toUomId)
            ->where('to_uom_id', $fromUomId)
            ->value('factor');

        if ($inverse !== null && (float) $inverse > 0) {
            return round($qty / (float) $inverse, 4);
        }

        throw ValidationException::withMessages([
            'uom' => 'No conversion factor defined between the selected units for this item.',
        ]);
    }

    /**
     * Quantity in the item's stock UOM.
     */
    public function toStockQty(Item $item, float $qty, int $transactionUomId): float
    {
        $stockUomId = (int) $item->stock_uom_id;

        return $this->convert($item->id, $qty, $transactionUomId, $stockUomId);
    }
}
