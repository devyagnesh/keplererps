<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sales return line, optionally linked to the originating invoice line.
 *
 * @property int $id
 * @property int $sales_return_id
 * @property int|null $sales_invoice_item_id
 * @property int $item_id
 * @property int|null $batch_id
 */
class SalesReturnItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'sales_return_id',
        'sales_invoice_item_id',
        'item_id',
        'uom_id',
        'batch_id',
        'quantity',
        'rate',
        'gst_rate',
        'taxable_amount',
        'tax_amount',
        'line_total',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'rate' => 'decimal:4',
            'gst_rate' => 'decimal:2',
            'taxable_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function salesInvoiceItem(): BelongsTo
    {
        return $this->belongsTo(SalesInvoiceItem::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
}
