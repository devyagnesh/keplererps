<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Scheduled register report email delivery (M15).
 */
class ScheduledReport extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'register_key',
        'frequency',
        'recipient_emails',
        'is_active',
        'last_run_at',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
