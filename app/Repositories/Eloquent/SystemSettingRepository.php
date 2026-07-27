<?php

namespace App\Repositories\Eloquent;

use App\Models\SystemSetting;
use App\Repositories\Interfaces\SystemSettingRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Eloquent system setting repository.
 */
class SystemSettingRepository implements SystemSettingRepositoryInterface
{
    public function allGrouped(): Collection
    {
        return SystemSetting::query()
            ->orderBy('group_key')
            ->orderBy('setting_key')
            ->get()
            ->groupBy('group_key');
    }

    public function findByKey(string $key): ?SystemSetting
    {
        return SystemSetting::query()->where('setting_key', $key)->first();
    }

    public function getValue(string $key, mixed $default = null): mixed
    {
        $row = $this->findByKey($key);

        return $row?->typedValue() ?? $default;
    }

    public function upsertMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $row = $this->findByKey((string) $key);
            if ($row === null || $row->is_locked) {
                continue;
            }

            $stored = match ($row->value_type) {
                'boolean' => $value ? '1' : '0',
                'json' => is_string($value) ? $value : json_encode($value),
                default => (string) $value,
            };

            $row->forceFill(['setting_value' => $stored])->save();
        }
    }
}
