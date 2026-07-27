<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-item UOM conversion factor.
 *
 * @property int $id
 * @property int $item_id
 * @property int $from_uom_id
 * @property int $to_uom_id
 * @property string $factor
 */
class ItemUomConversion extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'item_id',
        'from_uom_id',
        'to_uom_id',
        'factor',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'factor' => 'decimal:6',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function fromUom(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'from_uom_id');
    }

    public function toUom(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'to_uom_id');
    }
}
