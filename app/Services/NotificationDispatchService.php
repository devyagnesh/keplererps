<?php

namespace App\Services;

use App\Enums\NotificationEvent;
use App\Enums\NotificationRecipientType;
use App\Models\NotificationRule;
use App\Models\User;
use App\Notifications\CatalogueNotification;
use App\Repositories\Interfaces\NotificationRuleRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Resolves catalogue rules and fans out in-app database notifications (M16).
 */
class NotificationDispatchService
{
    public function __construct(protected NotificationRuleRepositoryInterface $rules) {}

    /**
     * Dispatch every active in-app rule for a business event.
     *
     * @param  array<string, string>  $placeholders
     */
    public function dispatch(NotificationEvent $event, array $placeholders = [], ?string $url = null): int
    {
        $rules = $this->rules->activeForEvent($event);
        $sent = 0;

        foreach ($rules as $rule) {
            $sent += $this->dispatchRule($rule, $placeholders, $url);
        }

        return $sent;
    }

    /**
     * @param  array<string, string>  $placeholders
     */
    public function dispatchRule(NotificationRule $rule, array $placeholders = [], ?string $url = null): int
    {
        if (! $rule->is_active || ! $rule->channel->isSupported()) {
            return 0;
        }

        $recipients = $this->resolveRecipients($rule);

        if ($recipients->isEmpty()) {
            Log::info('Notification rule matched no recipients.', [
                'rule' => $rule->code,
                'event' => $rule->event->value,
            ]);

            return 0;
        }

        Notification::send(
            $recipients,
            (new CatalogueNotification($rule, $placeholders, $url))->afterCommit()
        );

        return $recipients->count();
    }

    /**
     * @return Collection<int, User>
     */
    protected function resolveRecipients(NotificationRule $rule): Collection
    {
        $users = User::query()->where('is_active', true)->with(['roles', 'permissions'])->get();

        return $users->filter(function (User $user) use ($rule): bool {
            return match ($rule->recipient_type) {
                NotificationRecipientType::Role => $user->hasRole($rule->recipient_value),
                NotificationRecipientType::Permission => $user->hasPermissionTo($rule->recipient_value),
            };
        })->values();
    }
}
