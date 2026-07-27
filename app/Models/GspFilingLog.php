<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit row for a GSP worksheet push / dry-run.
 */
class GspFilingLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'return_type',
        'period_from',
        'period_to',
        'status',
        'row_count',
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
            'period_from' => 'date',
            'period_to' => 'date',
            'payload' => 'array',
            'response' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
