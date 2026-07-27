<?php

namespace App\Models;

use App\Enums\ChargeAllocationBasis;
use App\Enums\GrnStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Goods receipt note header (M07).
 *
 * @property int $id
 * @property string $document_no
 * @property \Illuminate\Support\Carbon $document_date
 * @property int $purchase_order_id
 * @property int $supplier_id
 * @property int $warehouse_id
 * @property string $supplier_invoice_no
 * @property \Illuminate\Support\Carbon $supplier_invoice_date
 * @property GrnStatus $status
 */
class GoodsReceipt extends Model
{
    /** @use HasFactory<\Database\Factories\GoodsReceiptFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_no',
        'document_date',
        'purchase_order_id',
        'supplier_id',
        'warehouse_id',
        'supplier_invoice_no',
        'supplier_invoice_date',
        'vehicle_number',
        'freight_charges',
        'other_charges',
        'charge_allocation_basis',
        'status',
        'remarks',
        'posted_at',
        'posted_by',
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
            'supplier_invoice_date' => 'date',
            'status' => GrnStatus::class,
            'freight_charges' => 'decimal:2',
            'other_charges' => 'decimal:2',
            'charge_allocation_basis' => ChargeAllocationBasis::class,
            'posted_at' => 'datetime',
        ];
    }

    /**
     * Total header charges absorbed into item cost on post.
     */
    public function totalCharges(): float
    {
        return round((float) $this->freight_charges + (float) $this->other_charges, 2);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'supplier_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class)->orderBy('sort_order');
    }
}
