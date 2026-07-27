<?php

namespace App\Services;

use App\Models\ApprovalRule;
use App\Models\DocumentApprovalAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Threshold + multi-step approval checks with escalation (C3).
 */
class ApprovalRuleService
{
    /**
     * @return \Illuminate\Support\Collection<int, ApprovalRule>
     */
    public function all()
    {
        return ApprovalRule::query()->orderBy('document_type')->orderBy('code')->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ApprovalRule
    {
        $steps = $this->normalizeStepsInput($data);

        return ApprovalRule::query()->create([
            'code' => strtoupper((string) $data['code']),
            'name' => $data['name'],
            'document_type' => $data['document_type'],
            'condition_field' => $data['condition_field'] ?? 'grand_total',
            'condition_operator' => $data['condition_operator'] ?? 'gte',
            'condition_value' => round((float) ($data['condition_value'] ?? 0), 2),
            'approver_permission' => $data['approver_permission'] ?? ($steps[0]['permission'] ?? ''),
            'approval_mode' => in_array($data['approval_mode'] ?? 'sequential', ['sequential', 'parallel'], true)
                ? ($data['approval_mode'] ?? 'sequential')
                : 'sequential',
            'escalation_hours' => isset($data['escalation_hours']) ? (int) $data['escalation_hours'] : null,
            'auto_approve_below' => array_key_exists('auto_approve_below', $data) && $data['auto_approve_below'] !== null && $data['auto_approve_below'] !== ''
                ? round((float) $data['auto_approve_below'], 2)
                : null,
            'steps' => $steps,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): ApprovalRule
    {
        $rule = ApprovalRule::query()->findOrFail($id);
        $payload = [
            'name' => $data['name'] ?? $rule->name,
            'document_type' => $data['document_type'] ?? $rule->document_type,
            'condition_field' => $data['condition_field'] ?? $rule->condition_field,
            'condition_operator' => $data['condition_operator'] ?? $rule->condition_operator,
            'condition_value' => array_key_exists('condition_value', $data)
                ? round((float) $data['condition_value'], 2)
                : $rule->condition_value,
            'approver_permission' => $data['approver_permission'] ?? $rule->approver_permission,
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $rule->is_active,
        ];

        if (array_key_exists('approval_mode', $data)) {
            $payload['approval_mode'] = in_array($data['approval_mode'], ['sequential', 'parallel'], true)
                ? $data['approval_mode']
                : $rule->approval_mode;
        }
        if (array_key_exists('escalation_hours', $data)) {
            $payload['escalation_hours'] = $data['escalation_hours'] !== null && $data['escalation_hours'] !== ''
                ? (int) $data['escalation_hours']
                : null;
        }
        if (array_key_exists('auto_approve_below', $data)) {
            $payload['auto_approve_below'] = $data['auto_approve_below'] !== null && $data['auto_approve_below'] !== ''
                ? round((float) $data['auto_approve_below'], 2)
                : null;
        }
        if (array_key_exists('steps', $data) || array_key_exists('approver_permission', $data)) {
            $payload['steps'] = $this->normalizeStepsInput($data + [
                'approver_permission' => $payload['approver_permission'],
            ]);
            $payload['approver_permission'] = $payload['steps'][0]['permission'] ?? $payload['approver_permission'];
        }

        $rule->update($payload);

        return $rule->fresh();
    }

    public function delete(int $id): bool
    {
        return (bool) ApprovalRule::query()->findOrFail($id)->delete();
    }

    /**
     * Open pending step actions for a document that matches active rules.
     *
     * @param  array<string, mixed>  $document
     * @return list<DocumentApprovalAction>
     */
    public function startWorkflow(string $documentType, int $documentId, array $document): array
    {
        $amount = (float) ($document['grand_total'] ?? $document['condition_value'] ?? 0);
        $created = [];

        foreach ($this->matchingRules($documentType, $document) as $rule) {
            if ($rule->auto_approve_below !== null && $amount < (float) $rule->auto_approve_below) {
                continue;
            }

            $exists = DocumentApprovalAction::query()
                ->where('document_type', $documentType)
                ->where('document_id', $documentId)
                ->where('approval_rule_id', $rule->id)
                ->exists();

            if ($exists) {
                continue;
            }

            $steps = $rule->normalizedSteps();
            $dueAt = $rule->escalation_hours
                ? now()->addHours((int) $rule->escalation_hours)
                : null;

            if ($rule->approval_mode === 'parallel') {
                foreach ($steps as $index => $step) {
                    $created[] = DocumentApprovalAction::query()->create([
                        'document_type' => $documentType,
                        'document_id' => $documentId,
                        'approval_rule_id' => $rule->id,
                        'step_index' => $index,
                        'required_permission' => $step['permission'],
                        'status' => 'pending',
                        'due_at' => $dueAt,
                    ]);
                }
            } else {
                $first = $steps[0];
                $created[] = DocumentApprovalAction::query()->create([
                    'document_type' => $documentType,
                    'document_id' => $documentId,
                    'approval_rule_id' => $rule->id,
                    'step_index' => 0,
                    'required_permission' => $first['permission'],
                    'status' => 'pending',
                    'due_at' => $dueAt,
                ]);
            }
        }

        return $created;
    }

    /**
     * Assert the current user may approve a document value under active rules / open steps.
     *
     * @param  array<string, mixed>  $document  Must include the condition field (e.g. grand_total).
     */
    public function assertCanApprove(string $documentType, array $document): void
    {
        $documentId = isset($document['id']) ? (int) $document['id'] : null;
        $amount = (float) ($document[$this->firstConditionField($documentType)] ?? $document['grand_total'] ?? 0);
        $user = Auth::user();
        $rules = $this->matchingRules($documentType, $document);

        if ($rules->isEmpty()) {
            return;
        }

        foreach ($rules as $rule) {
            if ($rule->auto_approve_below !== null && $amount < (float) $rule->auto_approve_below) {
                continue;
            }

            if ($documentId !== null) {
                $this->startWorkflow($documentType, $documentId, $document);
                $this->assertOpenSteps($documentType, $documentId, $rule, $user);

                continue;
            }

            $this->assertLegacyPermission($rule, $user);
        }
    }

    /**
     * Record approval for the current user's eligible open step(s).
     */
    public function recordApproval(string $documentType, int $documentId, ?string $remarks = null): void
    {
        $user = Auth::user();
        if ($user === null) {
            throw ValidationException::withMessages(['approval' => 'Authentication required for approval.']);
        }

        DB::transaction(function () use ($documentType, $documentId, $remarks, $user): void {
            $pending = DocumentApprovalAction::query()
                ->with('rule')
                ->where('document_type', $documentType)
                ->where('document_id', $documentId)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->orderBy('step_index')
                ->get();

            if ($pending->isEmpty()) {
                return;
            }

            $acted = false;

            foreach ($pending as $action) {
                if (! $user->hasPermissionTo($action->required_permission)) {
                    continue;
                }

                $action->forceFill([
                    'status' => 'approved',
                    'acted_by' => $user->id,
                    'acted_at' => now(),
                    'remarks' => $remarks,
                ])->save();
                $acted = true;

                $rule = $action->rule;
                if ($rule && $rule->approval_mode === 'sequential') {
                    $steps = $rule->normalizedSteps();
                    $nextIndex = $action->step_index + 1;
                    if (isset($steps[$nextIndex])) {
                        DocumentApprovalAction::query()->create([
                            'document_type' => $documentType,
                            'document_id' => $documentId,
                            'approval_rule_id' => $rule->id,
                            'step_index' => $nextIndex,
                            'required_permission' => $steps[$nextIndex]['permission'],
                            'status' => 'pending',
                            'due_at' => $rule->escalation_hours
                                ? now()->addHours((int) $rule->escalation_hours)
                                : null,
                        ]);
                    }
                }
            }

            if (! $acted) {
                throw ValidationException::withMessages([
                    'approval' => 'You do not have permission for the current approval step.',
                ]);
            }
        });
    }

    /**
     * Mark overdue pending steps as escalated. Returns count updated.
     */
    public function escalateDue(): int
    {
        $due = DocumentApprovalAction::query()
            ->where('status', 'pending')
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now())
            ->get();

        foreach ($due as $action) {
            $action->forceFill([
                'status' => 'escalated',
                'escalated_at' => now(),
            ])->save();
        }

        return $due->count();
    }

    /**
     * @return \Illuminate\Support\Collection<int, ApprovalRule>
     */
    protected function matchingRules(string $documentType, array $document)
    {
        return ApprovalRule::query()
            ->where('document_type', $documentType)
            ->where('is_active', true)
            ->get()
            ->filter(function (ApprovalRule $rule) use ($document): bool {
                $value = (float) ($document[$rule->condition_field] ?? 0);
                $threshold = (float) $rule->condition_value;

                return match ($rule->condition_operator) {
                    'gt' => $value > $threshold,
                    'lte' => $value <= $threshold,
                    'lt' => $value < $threshold,
                    default => $value >= $threshold,
                };
            })
            ->values();
    }

    protected function firstConditionField(string $documentType): string
    {
        return (string) (ApprovalRule::query()
            ->where('document_type', $documentType)
            ->where('is_active', true)
            ->value('condition_field') ?? 'grand_total');
    }

    /**
     * @param  \App\Models\User|null  $user
     */
    protected function assertOpenSteps(string $documentType, int $documentId, ApprovalRule $rule, $user): void
    {
        $pending = DocumentApprovalAction::query()
            ->where('document_type', $documentType)
            ->where('document_id', $documentId)
            ->where('approval_rule_id', $rule->id)
            ->whereIn('status', ['pending', 'escalated'])
            ->orderBy('step_index')
            ->get();

        if ($pending->isEmpty()) {
            return;
        }

        if ($user === null) {
            throw ValidationException::withMessages([
                'approval' => "Approval requires permission under rule {$rule->code}.",
            ]);
        }

        $eligible = $pending->first(fn (DocumentApprovalAction $action): bool => $user->hasPermissionTo($action->required_permission));

        if ($eligible === null) {
            $needed = $pending->pluck('required_permission')->unique()->implode(', ');
            throw ValidationException::withMessages([
                'approval' => "Approval requires permission {$needed} under rule {$rule->code}.",
            ]);
        }

        $this->recordApproval($documentType, $documentId);

        $stillPending = DocumentApprovalAction::query()
            ->where('document_type', $documentType)
            ->where('document_id', $documentId)
            ->where('approval_rule_id', $rule->id)
            ->whereIn('status', ['pending', 'escalated'])
            ->exists();

        if ($stillPending) {
            throw ValidationException::withMessages([
                'approval' => "Document is awaiting additional approval steps under rule {$rule->code}.",
            ]);
        }
    }

    /**
     * @param  \App\Models\User|null  $user
     */
    protected function assertLegacyPermission(ApprovalRule $rule, $user): void
    {
        foreach ($rule->normalizedSteps() as $step) {
            if ($user === null || ! $user->hasPermissionTo($step['permission'])) {
                throw ValidationException::withMessages([
                    'approval' => "Approval requires permission {$step['permission']} under rule {$rule->code}.",
                ]);
            }

            if ($rule->approval_mode === 'sequential') {
                // Sequential without document id: first step permission is enough to proceed.
                return;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{permission: string, label: string}>
     */
    protected function normalizeStepsInput(array $data): array
    {
        $raw = $data['steps'] ?? null;

        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $raw = $decoded;
            } else {
                $raw = array_map('trim', explode(',', $raw));
            }
        }

        $steps = [];
        if (is_array($raw)) {
            foreach ($raw as $step) {
                $permission = is_array($step)
                    ? (string) ($step['permission'] ?? '')
                    : (string) $step;
                if ($permission === '') {
                    continue;
                }
                $steps[] = [
                    'permission' => $permission,
                    'label' => is_array($step) ? (string) ($step['label'] ?? $permission) : $permission,
                ];
            }
        }

        if ($steps === [] && ! empty($data['approver_permission'])) {
            $steps[] = [
                'permission' => (string) $data['approver_permission'],
                'label' => (string) $data['approver_permission'],
            ];
        }

        return $steps;
    }
}
