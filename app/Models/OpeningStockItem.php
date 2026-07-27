<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Opening stock line.
 *
 * @property int $id
 * @property int $opening_stock_id
 * @property int $item_id
 * @property int|null $batch_id
 * @property string|null $batch_no
 * @property \Illuminate\Support\Carbon|null $mfg_date
 * @property \Illuminate\Support\Carbon|null $expiry_date
 * @property string|null $serial_no
 * @property string $quantity
 * @property string $rate
 * @property string $value
 */
class OpeningStockItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'opening_stock_id',
        'item_id',
        'batch_id',
        'batch_no',
        'mfg_date',
        'expiry_date',
        'serial_no',
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
            'mfg_date' => 'date',
            'expiry_date' => 'date',
            'quantity' => 'decimal:4',
            'rate' => 'decimal:4',
            'value' => 'decimal:2',
        ];
    }

    public function openingStock(): BelongsTo
    {
        return $this->belongsTo(OpeningStock::class);
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
