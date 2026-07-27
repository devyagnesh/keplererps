<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-warehouse reorder and stock band settings for an item.
 *
 * @property int $id
 * @property int $item_id
 * @property int $warehouse_id
 * @property string $reorder_level
 * @property string|null $reorder_qty
 * @property string|null $min_stock
 * @property string|null $max_stock
 */
class ItemWarehouseSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'item_id',
        'warehouse_id',
        'reorder_level',
        'reorder_qty',
        'min_stock',
        'max_stock',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reorder_level' => 'decimal:4',
            'reorder_qty' => 'decimal:4',
            'min_stock' => 'decimal:4',
            'max_stock' => 'decimal:4',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
