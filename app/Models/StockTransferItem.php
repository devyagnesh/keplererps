<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock transfer line.
 *
 * @property int $id
 * @property int $stock_transfer_id
 * @property int $item_id
 * @property int|null $batch_id
 * @property string $quantity
 * @property string $rate
 * @property string $value
 */
class StockTransferItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'stock_transfer_id',
        'item_id',
        'batch_id',
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
            'quantity' => 'decimal:4',
            'rate' => 'decimal:4',
            'value' => 'decimal:2',
        ];
    }

    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class);
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
