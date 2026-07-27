<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One approval step action for a document under a rule.
 */
class DocumentApprovalAction extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_type',
        'document_id',
        'approval_rule_id',
        'step_index',
        'required_permission',
        'status',
        'acted_by',
        'acted_at',
        'due_at',
        'escalated_at',
        'remarks',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'acted_at' => 'datetime',
            'due_at' => 'datetime',
            'escalated_at' => 'datetime',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ApprovalRule::class, 'approval_rule_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}
