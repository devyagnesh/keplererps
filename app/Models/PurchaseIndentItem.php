<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Line on a purchase indent.
 */
class PurchaseIndentItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'purchase_indent_id',
        'item_id',
        'uom_id',
        'quantity',
        'base_qty',
        'ordered_qty',
        'source',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'base_qty' => 'decimal:4',
            'ordered_qty' => 'decimal:4',
        ];
    }

    public function indent(): BelongsTo
    {
        return $this->belongsTo(PurchaseIndent::class, 'purchase_indent_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }

    public function pendingQty(): float
    {
        return max(0, round((float) $this->quantity - (float) $this->ordered_qty, 4));
    }
}
