<?php

namespace App\Repositories\Interfaces;

use App\Models\SystemSetting;
use Illuminate\Support\Collection;

/**
 * Data-access contract for system settings.
 */
interface SystemSettingRepositoryInterface
{
    /** @return Collection<int, SystemSetting> */
    public function allGrouped(): Collection;

    public function findByKey(string $key): ?SystemSetting;

    public function getValue(string $key, mixed $default = null): mixed;

    /**
     * @param  array<string, mixed>  $values
     */
    public function upsertMany(array $values): void;
}
