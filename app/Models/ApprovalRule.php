<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Threshold + multi-step approval workflow rule (C3).
 */
class ApprovalRule extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'document_type',
        'condition_field',
        'condition_operator',
        'condition_value',
        'approver_permission',
        'approval_mode',
        'escalation_hours',
        'auto_approve_below',
        'steps',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'condition_value' => 'decimal:2',
            'auto_approve_below' => 'decimal:2',
            'escalation_hours' => 'integer',
            'steps' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Ordered permission steps for this rule.
     *
     * @return list<array{permission: string, label?: string}>
     */
    public function normalizedSteps(): array
    {
        $steps = $this->steps;
        if (is_array($steps) && $steps !== []) {
            $normalized = [];
            foreach ($steps as $step) {
                $permission = is_array($step)
                    ? (string) ($step['permission'] ?? '')
                    : (string) $step;
                if ($permission === '') {
                    continue;
                }
                $normalized[] = [
                    'permission' => $permission,
                    'label' => is_array($step) ? (string) ($step['label'] ?? $permission) : $permission,
                ];
            }

            if ($normalized !== []) {
                return $normalized;
            }
        }

        return [[
            'permission' => (string) $this->approver_permission,
            'label' => (string) $this->approver_permission,
        ]];
    }

    public function actions(): HasMany
    {
        return $this->hasMany(DocumentApprovalAction::class);
    }
}
