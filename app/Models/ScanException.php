<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Barcode / scan exception log for warehouse floor devices.
 */
class ScanException extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'scan_code',
        'context',
        'reason',
        'device_id',
        'payload',
        'status',
        'resolved_by',
        'resolved_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
