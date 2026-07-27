<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Purchase order line.
 *
 * @property int $id
 * @property int $purchase_order_id
 * @property int $item_id
 * @property int $uom_id
 * @property string $quantity
 * @property string $rate
 * @property string $gst_rate
 * @property string $tax_amount
 * @property string $line_total
 * @property string $tolerance_percent
 * @property string $received_qty
 * @property bool $requires_inspection
 */
class PurchaseOrderItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'purchase_order_id',
        'item_id',
        'uom_id',
        'quantity',
        'base_qty',
        'rate',
        'gst_rate',
        'tax_amount',
        'line_total',
        'tolerance_percent',
        'received_qty',
        'requires_inspection',
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
            'rate' => 'decimal:4',
            'gst_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
            'tolerance_percent' => 'decimal:2',
            'received_qty' => 'decimal:4',
            'requires_inspection' => 'boolean',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }

    /**
     * Remaining receivable quantity including tolerance.
     */
    public function pendingQty(): float
    {
        $ordered = (float) $this->quantity;
        $tolerance = $ordered * ((float) $this->tolerance_percent / 100);
        $maxReceivable = $ordered + $tolerance;

        return max(0, round($maxReceivable - (float) $this->received_qty, 4));
    }

    /**
     * Pending against ordered quantity (excluding unused tolerance headroom).
     */
    public function pendingOrderedQty(): float
    {
        return max(0, round((float) $this->quantity - (float) $this->received_qty, 4));
    }
}
