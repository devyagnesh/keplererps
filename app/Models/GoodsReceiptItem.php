<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Goods receipt line.
 *
 * @property int $id
 * @property int $goods_receipt_id
 * @property int $purchase_order_item_id
 * @property int $item_id
 * @property string $received_qty
 * @property string $accepted_qty
 * @property string $rejected_qty
 * @property string|null $rejection_reason
 * @property string $rate
 * @property string|null $batch_no
 * @property string|null $serial_no
 */
class GoodsReceiptItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'goods_receipt_id',
        'purchase_order_item_id',
        'item_id',
        'received_qty',
        'accepted_qty',
        'rejected_qty',
        'rejection_reason',
        'rate',
        'allocated_charge',
        'landed_rate',
        'batch_no',
        'mfg_date',
        'expiry_date',
        'serial_no',
        'batch_id',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'received_qty' => 'decimal:4',
            'accepted_qty' => 'decimal:4',
            'rejected_qty' => 'decimal:4',
            'rate' => 'decimal:4',
            'allocated_charge' => 'decimal:2',
            'landed_rate' => 'decimal:4',
            'mfg_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
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
