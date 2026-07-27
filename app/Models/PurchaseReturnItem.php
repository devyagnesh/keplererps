<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Purchase return line linked to the originating goods receipt line.
 *
 * @property int $id
 * @property int $purchase_return_id
 * @property int $goods_receipt_item_id
 * @property int $item_id
 * @property int|null $batch_id
 */
class PurchaseReturnItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'purchase_return_id',
        'goods_receipt_item_id',
        'item_id',
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

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function goodsReceiptItem(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptItem::class);
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
