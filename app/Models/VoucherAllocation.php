<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Allocation of a receipt/payment voucher against an open invoice or bill (M13).
 */
class VoucherAllocation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'journal_voucher_id',
        'allocatable_type',
        'allocatable_id',
        'party_id',
        'amount',
        'remarks',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(JournalVoucher::class, 'journal_voucher_id');
    }

    public function allocatable(): MorphTo
    {
        return $this->morphTo();
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
}
