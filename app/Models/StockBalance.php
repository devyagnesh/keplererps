<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cached physical stock balance per item / warehouse / batch (SRS A3).
 *
 * @property int $id
 * @property int $item_id
 * @property int $warehouse_id
 * @property int|null $batch_id
 * @property int $batch_key
 * @property string $qty
 * @property string $committed_qty
 * @property string $on_order_qty
 * @property string $value
 */
class StockBalance extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'item_id',
        'warehouse_id',
        'batch_id',
        'batch_key',
        'qty',
        'committed_qty',
        'on_order_qty',
        'value',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty' => 'decimal:4',
            'committed_qty' => 'decimal:4',
            'on_order_qty' => 'decimal:4',
            'value' => 'decimal:2',
            'batch_key' => 'integer',
        ];
    }

    /**
     * Available (free) quantity = physical − committed.
     */
    public function availableQty(): float
    {
        return (float) $this->qty - (float) $this->committed_qty;
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
}
