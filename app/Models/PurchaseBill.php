<?php

namespace App\Models;

use App\Enums\MatchStatus;
use App\Enums\PurchaseBillStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Supplier purchase bill header with three-way match state (US-M07-04).
 *
 * @property int $id
 * @property string $document_no
 * @property \Illuminate\Support\Carbon $document_date
 * @property int $supplier_id
 * @property int|null $purchase_order_id
 * @property int|null $goods_receipt_id
 * @property string $supplier_bill_no
 * @property \Illuminate\Support\Carbon $supplier_bill_date
 * @property PurchaseBillStatus $status
 * @property MatchStatus $match_status
 */
class PurchaseBill extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseBillFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_no',
        'document_date',
        'supplier_id',
        'purchase_order_id',
        'goods_receipt_id',
        'supplier_bill_no',
        'supplier_bill_date',
        'status',
        'match_status',
        'subtotal',
        'tax_total',
        'other_charges',
        'round_off',
        'grand_total',
        'mismatch_reason',
        'approved_at',
        'approved_by',
        'remarks',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'supplier_bill_date' => 'date',
            'status' => PurchaseBillStatus::class,
            'match_status' => MatchStatus::class,
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'other_charges' => 'decimal:2',
            'round_off' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'supplier_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseBillItem::class)->orderBy('sort_order');
    }
}
