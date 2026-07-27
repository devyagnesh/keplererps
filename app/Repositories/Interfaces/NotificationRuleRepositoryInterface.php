<?php

namespace App\Repositories\Interfaces;

use App\Enums\NotificationEvent;
use App\Models\NotificationRule;
use Illuminate\Support\Collection;

/**
 * Data-access contract for notification rules.
 */
interface NotificationRuleRepositoryInterface
{
    /**
     * @return Collection<int, NotificationRule>
     */
    public function all(): Collection;

    public function findById(int $id): NotificationRule;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): NotificationRule;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): NotificationRule;

    public function delete(int $id): bool;

    /**
     * Active in-app rules for a business event.
     *
     * @return Collection<int, NotificationRule>
     */
    public function activeForEvent(NotificationEvent $event): Collection;
}
