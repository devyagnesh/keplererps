<?php

namespace App\Models;

use App\Enums\RfqStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Request for quotation header (M07).
 */
class PurchaseRfq extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_no',
        'document_date',
        'valid_until',
        'warehouse_id',
        'purchase_indent_id',
        'status',
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
            'valid_until' => 'date',
            'status' => RfqStatus::class,
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function indent(): BelongsTo
    {
        return $this->belongsTo(PurchaseIndent::class, 'purchase_indent_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRfqItem::class)->orderBy('sort_order');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(PurchaseRfqQuote::class);
    }
}
