<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Quoted rate for one RFQ line.
 */
class PurchaseRfqQuoteItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'purchase_rfq_quote_id',
        'purchase_rfq_item_id',
        'rate',
        'gst_rate',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:4',
            'gst_rate' => 'decimal:2',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(PurchaseRfqQuote::class, 'purchase_rfq_quote_id');
    }

    public function rfqItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseRfqItem::class, 'purchase_rfq_item_id');
    }
}
