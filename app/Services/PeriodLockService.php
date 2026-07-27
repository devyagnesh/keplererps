<?php

namespace App\Services;

use App\Models\PeriodLock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Period lock by date with optional override permission (US-M13-06).
 */
class PeriodLockService
{
    public function __construct(protected ActivityLogService $activityLog) {}

    public function current(): ?PeriodLock
    {
        return PeriodLock::query()
            ->where('is_active', true)
            ->orderByDesc('locked_to')
            ->first();
    }

    /**
     * @param  array{locked_to: string, reason?: string|null}  $data
     */
    public function lock(array $data): PeriodLock
    {
        PeriodLock::query()->where('is_active', true)->update(['is_active' => false]);

        $lock = PeriodLock::query()->create([
            'locked_to' => $data['locked_to'],
            'reason' => $data['reason'] ?? null,
            'locked_by' => Auth::id(),
            'locked_at' => now(),
            'is_active' => true,
        ]);

        $this->activityLog->log(
            event: 'period_locked',
            description: "Accounting period locked through {$lock->locked_to->toDateString()}.",
            subject: $lock,
            properties: ['locked_to' => $lock->locked_to->toDateString(), 'reason' => $lock->reason],
            logName: 'finance'
        );

        return $lock;
    }

    /**
     * Assert a document date is allowed; override users may pass a reason.
     *
     * @throws ValidationException
     */
    public function assertOpen(string $documentDate, ?string $overrideReason = null): void
    {
        $lock = $this->current();
        if ($lock === null) {
            return;
        }

        if ($documentDate > $lock->locked_to->toDateString()) {
            return;
        }

        $user = Auth::user();
        $canOverride = $user !== null && $user->hasPermissionTo('period_lock.override');

        if ($canOverride && filled($overrideReason)) {
            $this->activityLog->log(
                event: 'period_lock_override',
                description: "Period lock overridden for {$documentDate}.",
                subject: $lock,
                properties: ['document_date' => $documentDate, 'reason' => $overrideReason],
                logName: 'finance'
            );

            return;
        }

        throw ValidationException::withMessages([
            'document_date' => 'The accounting period is locked through '.$lock->locked_to->toDateString()
                .($canOverride ? '. Provide an override reason to continue.' : '.'),
        ]);
    }
}
