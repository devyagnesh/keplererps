<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GSP e-way bill submission attempt log (US-M12-02).
 */
class EwaySubmissionLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'delivery_challan_id',
        'status',
        'eway_bill_number',
        'payload',
        'response',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'response' => 'array',
        ];
    }

    public function deliveryChallan(): BelongsTo
    {
        return $this->belongsTo(DeliveryChallan::class);
    }
}
