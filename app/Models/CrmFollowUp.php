<?php

namespace App\Models;

use App\Enums\FollowUpMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Follow-up entry logged against a lead or an opportunity (M05).
 *
 * @property int $id
 * @property string $followupable_type
 * @property int $followupable_id
 * @property FollowUpMode $mode
 */
class CrmFollowUp extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'followupable_type',
        'followupable_id',
        'follow_up_date',
        'mode',
        'summary',
        'outcome',
        'next_follow_up_date',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'follow_up_date' => 'date',
            'next_follow_up_date' => 'date',
            'mode' => FollowUpMode::class,
        ];
    }

    public function followupable(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
