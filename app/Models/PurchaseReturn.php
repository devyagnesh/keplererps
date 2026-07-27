<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Purchase return (debit note) header — issues stock back to the supplier.
 *
 * @property int $id
 * @property string $document_no
 * @property \Illuminate\Support\Carbon $document_date
 * @property int $supplier_id
 * @property int $goods_receipt_id
 * @property int $warehouse_id
 * @property DocumentStatus $status
 */
class PurchaseReturn extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_no',
        'document_date',
        'supplier_id',
        'goods_receipt_id',
        'warehouse_id',
        'status',
        'reason',
        'subtotal',
        'tax_total',
        'grand_total',
        'remarks',
        'posted_at',
        'posted_by',
        'created_by',
        'updated_by',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'status' => DocumentStatus::class,
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'posted_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'supplier_id');
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class)->orderBy('sort_order');
    }

    public function ledgerEntries(): MorphMany
    {
        return $this->morphMany(StockLedgerEntry::class, 'source');
    }
}
