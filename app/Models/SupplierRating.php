<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Computed supplier performance score (M07).
 */
class SupplierRating extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'party_id',
        'period_from',
        'period_to',
        'po_count',
        'on_time_count',
        'qc_fail_count',
        'otif_score',
        'quality_score',
        'overall_score',
        'computed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_from' => 'date',
            'period_to' => 'date',
            'otif_score' => 'decimal:2',
            'quality_score' => 'decimal:2',
            'overall_score' => 'decimal:2',
            'computed_at' => 'datetime',
        ];
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
}
