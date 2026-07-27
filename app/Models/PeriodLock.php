<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Accounting period lock cutoff date (M13 US-M13-06).
 */
class PeriodLock extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'locked_to',
        'reason',
        'locked_by',
        'locked_at',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'locked_to' => 'date',
            'locked_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }
}
