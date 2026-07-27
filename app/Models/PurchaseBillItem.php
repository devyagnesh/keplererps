<?php

namespace App\Models;

use App\Enums\MatchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Purchase bill line with per-line match variance (US-M07-04).
 *
 * @property int $id
 * @property int $purchase_bill_id
 * @property int|null $goods_receipt_item_id
 * @property int|null $purchase_order_item_id
 * @property int $item_id
 * @property MatchStatus $match_status
 */
class PurchaseBillItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'purchase_bill_id',
        'goods_receipt_item_id',
        'purchase_order_item_id',
        'item_id',
        'uom_id',
        'quantity',
        'rate',
        'gst_rate',
        'taxable_amount',
        'tax_amount',
        'line_total',
        'po_rate',
        'grn_qty',
        'rate_variance_percent',
        'qty_variance',
        'match_status',
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
            'po_rate' => 'decimal:4',
            'grn_qty' => 'decimal:4',
            'rate_variance_percent' => 'decimal:4',
            'qty_variance' => 'decimal:4',
            'match_status' => MatchStatus::class,
        ];
    }

    public function purchaseBill(): BelongsTo
    {
        return $this->belongsTo(PurchaseBill::class);
    }

    public function goodsReceiptItem(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptItem::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }
}
