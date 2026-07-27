<?php

namespace App\Models;

use App\Enums\AdjustmentDirection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock adjustment line.
 *
 * @property int $id
 * @property int $stock_adjustment_id
 * @property int $item_id
 * @property int|null $batch_id
 * @property AdjustmentDirection $direction
 * @property string $quantity
 * @property string $rate
 * @property string $value
 */
class StockAdjustmentItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'stock_adjustment_id',
        'item_id',
        'batch_id',
        'direction',
        'quantity',
        'rate',
        'value',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'direction' => AdjustmentDirection::class,
            'quantity' => 'decimal:4',
            'rate' => 'decimal:4',
            'value' => 'decimal:2',
        ];
    }

    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
}
