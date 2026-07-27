<?php

namespace App\Repositories\Eloquent;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEvent;
use App\Models\NotificationRule;
use App\Repositories\Interfaces\NotificationRuleRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Eloquent implementation of NotificationRuleRepositoryInterface.
 */
class NotificationRuleRepository implements NotificationRuleRepositoryInterface
{
    public function __construct(protected NotificationRule $model) {}

    /**
     * {@inheritdoc}
     */
    public function all(): Collection
    {
        return $this->model->newQuery()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): NotificationRule
    {
        return $this->model->newQuery()->findOrFail($id);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): NotificationRule
    {
        return $this->model->newQuery()->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): NotificationRule
    {
        $rule = $this->findById($id);
        $rule->update($data);

        return $rule->fresh();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        return (bool) $this->findById($id)->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function activeForEvent(NotificationEvent $event): Collection
    {
        $supported = array_map(
            fn (NotificationChannel $channel): string => $channel->value,
            array_values(array_filter(
                NotificationChannel::cases(),
                fn (NotificationChannel $channel): bool => $channel->isSupported()
            ))
        );

        return $this->model->newQuery()
            ->where('event', $event->value)
            ->whereIn('channel', $supported)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}
