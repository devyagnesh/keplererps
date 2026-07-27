<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEvent;
use App\Enums\NotificationRecipientType;
use App\Models\NotificationRule;
use App\Models\Permission;
use App\Models\Role;
use App\Repositories\Interfaces\NotificationRuleRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Catalogue CRUD for in-app notification rules (M16).
 */
class NotificationRuleService
{
    public function __construct(protected NotificationRuleRepositoryInterface $repository) {}

    /**
     * @return Collection<int, NotificationRule>
     */
    public function all(): Collection
    {
        return $this->repository->all();
    }

    public function find(int $id): NotificationRule
    {
        return $this->repository->findById($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): NotificationRule
    {
        $data = $this->normalize($data);
        $this->assertRecipientExists($data['recipient_type'], $data['recipient_value']);

        if (empty($data['code'])) {
            $data['code'] = $this->generateCode($data['event'], $data['channel']);
        }

        return $this->repository->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): NotificationRule
    {
        $rule = $this->repository->findById($id);
        $data = $this->normalize($data, $rule);

        // System rules keep their event/channel/code so seeders stay stable.
        if ($rule->is_system) {
            unset($data['code'], $data['event'], $data['channel'], $data['is_system']);
        }

        $this->assertRecipientExists(
            $data['recipient_type'] ?? $rule->recipient_type->value,
            $data['recipient_value'] ?? $rule->recipient_value
        );

        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        $rule = $this->repository->findById($id);

        if ($rule->is_system) {
            throw ValidationException::withMessages([
                'notification_rule' => 'System notification rules cannot be deleted. Disable them instead.',
            ]);
        }

        return $this->repository->delete($id);
    }

    /**
     * Toggle active flag without a full form submit.
     */
    public function toggle(int $id): NotificationRule
    {
        $rule = $this->repository->findById($id);

        return $this->repository->update($id, ['is_active' => ! $rule->is_active]);
    }

    /**
     * Lookup data for the catalogue form.
     *
     * @return array{events: list<array{value: string, label: string, module: string}>, channels: list<array{value: string, label: string, supported: bool}>, recipient_types: list<array{value: string, label: string}>, roles: list<array{value: string, label: string}>, permissions: list<string>}
     */
    public function formLookups(): array
    {
        return [
            'events' => collect(NotificationEvent::cases())->map(fn (NotificationEvent $event): array => [
                'value' => $event->value,
                'label' => $event->label(),
                'module' => $event->module(),
            ])->values()->all(),
            'channels' => collect(NotificationChannel::cases())->map(fn (NotificationChannel $channel): array => [
                'value' => $channel->value,
                'label' => $channel->label(),
                'supported' => $channel->isSupported(),
            ])->values()->all(),
            'recipient_types' => collect(NotificationRecipientType::cases())->map(fn (NotificationRecipientType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ])->values()->all(),
            'roles' => Role::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['slug', 'name'])
                ->map(fn (Role $role): array => ['value' => $role->slug, 'label' => $role->name])
                ->values()
                ->all(),
            'permissions' => Permission::query()
                ->orderBy('name')
                ->pluck('name')
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalize(array $data, ?NotificationRule $existing = null): array
    {
        $channel = NotificationChannel::tryFrom((string) ($data['channel'] ?? $existing?->channel->value ?? 'in_app'))
            ?? NotificationChannel::InApp;

        if (! $channel->isSupported()) {
            throw ValidationException::withMessages([
                'channel' => $channel->label().' delivery is not enabled yet. Use In-app for now.',
            ]);
        }

        $data['channel'] = $channel->value;
        $data['is_active'] = filter_var($data['is_active'] ?? ($existing?->is_active ?? true), FILTER_VALIDATE_BOOLEAN);
        $data['code'] = Str::upper(Str::slug((string) ($data['code'] ?? ''), '_'));

        return $data;
    }

    protected function assertRecipientExists(string|NotificationRecipientType $type, string $value): void
    {
        $type = $type instanceof NotificationRecipientType ? $type : NotificationRecipientType::from($type);

        $exists = match ($type) {
            NotificationRecipientType::Role => Role::query()->where('slug', $value)->exists(),
            NotificationRecipientType::Permission => Permission::query()->where('name', $value)->exists(),
        };

        if (! $exists) {
            throw ValidationException::withMessages([
                'recipient_value' => 'The selected '.$type->label().' does not exist.',
            ]);
        }
    }

    protected function generateCode(string $event, string $channel): string
    {
        $base = Str::upper(Str::slug($event.'_'.$channel, '_'));
        $code = $base;
        $i = 1;

        while (NotificationRule::query()->where('code', $code)->exists()) {
            $code = $base.'_'.$i;
            $i++;
        }

        return $code;
    }
}
