<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sales order line with delivery/invoice progress (US-M06-04).
 */
class SalesOrderItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'sales_order_id',
        'item_id',
        'uom_id',
        'description',
        'quantity',
        'base_qty',
        'rate',
        'discount_percent',
        'discount_amount',
        'taxable_amount',
        'gst_rate',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'tax_amount',
        'line_total',
        'delivered_qty',
        'invoiced_qty',
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
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'taxable_amount' => 'decimal:2',
            'gst_rate' => 'decimal:2',
            'cgst_amount' => 'decimal:2',
            'sgst_amount' => 'decimal:2',
            'igst_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
            'delivered_qty' => 'decimal:4',
            'invoiced_qty' => 'decimal:4',
        ];
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }

    public function pendingDeliveryQty(): float
    {
        return max(0, round((float) $this->quantity - (float) $this->delivered_qty, 4));
    }

    public function pendingInvoiceQty(): float
    {
        return max(0, round((float) $this->quantity - (float) $this->invoiced_qty, 4));
    }
}
