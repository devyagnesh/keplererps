<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Supplier quote against an RFQ.
 */
class PurchaseRfqQuote extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'purchase_rfq_id',
        'supplier_id',
        'quote_date',
        'freight_amount',
        'lead_time_days',
        'is_selected',
        'award_reason',
        'remarks',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quote_date' => 'date',
            'freight_amount' => 'decimal:2',
            'is_selected' => 'boolean',
        ];
    }

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(PurchaseRfq::class, 'purchase_rfq_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'supplier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRfqQuoteItem::class);
    }

    public function lineTotal(): float
    {
        return round((float) $this->items->sum(function (PurchaseRfqQuoteItem $line): float {
            $rfqItem = $line->rfqItem;
            $qty = (float) ($rfqItem?->quantity ?? 0);

            return $qty * (float) $line->rate;
        }) + (float) $this->freight_amount, 2);
    }
}
