<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Price list line (M06).
 */
class PriceListItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'price_list_id',
        'item_id',
        'min_qty',
        'rate',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'min_qty' => 'decimal:4',
            'rate' => 'decimal:4',
        ];
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
