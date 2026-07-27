<?php

namespace App\Services;

use App\Enums\CostingMethod;
use App\Models\IndustryProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

/**
 * Active industry profile accessor — services use feature flags, never industry codes.
 */
class IndustryProfileService
{
    public function __construct(protected SystemSettingService $settings) {}

    /**
     * @return Collection<int, IndustryProfile>
     */
    public function all(): Collection
    {
        return IndustryProfile::query()->orderBy('name')->get();
    }

    public function active(): ?IndustryProfile
    {
        // Cache the id only — serializing Eloquent models can yield __PHP_Incomplete_Class
        // after deploy / classmap changes (sidebar crash).
        $id = Cache::remember('industry_profile.active_id', 300, function (): ?int {
            $profile = IndustryProfile::query()->where('is_active', true)->first();

            return $profile?->id;
        });

        if ($id === null) {
            return null;
        }

        $profile = IndustryProfile::query()->find($id);

        if ($profile === null) {
            Cache::forget('industry_profile.active_id');
        }

        return $profile;
    }

    public function feature(string $flag, bool $default = false): bool
    {
        $profile = $this->active();
        if ($profile === null) {
            return $default;
        }

        $modules = $profile->modules ?? [];

        return (bool) ($modules[$flag] ?? $default);
    }

    public function costingMethod(): CostingMethod
    {
        $profile = $this->active();
        $fromProfile = is_array($profile?->costing)
            ? (string) ($profile->costing['method'] ?? '')
            : '';

        if ($fromProfile !== '' && CostingMethod::tryFrom($fromProfile) !== null) {
            return CostingMethod::from($fromProfile);
        }

        return $this->settings->costingMethod();
    }

    /**
     * Activate one industry pack; deactivates others and syncs costing setting.
     */
    public function activate(string $code): IndustryProfile
    {
        $profile = IndustryProfile::query()->where('code', $code)->firstOrFail();

        IndustryProfile::query()->where('is_active', true)->update(['is_active' => false]);
        $profile->forceFill(['is_active' => true])->save();

        $method = is_array($profile->costing) ? (string) ($profile->costing['method'] ?? '') : '';
        if ($method !== '' && CostingMethod::tryFrom($method) !== null) {
            try {
                $this->settings->update(['costing_method' => $method]);
            } catch (ValidationException) {
                // Locked costing — keep profile active without forcing the setting.
            }
        }

        $this->settings->update([
            'industry_profile_code' => $profile->code,
        ]);

        Cache::forget('industry_profile.active_id');
        Cache::forget('industry_profile.active');

        return $profile->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsert(array $data): IndustryProfile
    {
        $profile = IndustryProfile::query()->updateOrCreate(
            ['code' => $data['code']],
            [
                'name' => $data['name'],
                'modules' => $data['modules'] ?? [],
                'uom' => $data['uom'] ?? [],
                'costing' => $data['costing'] ?? [],
                'item_attributes' => $data['item_attributes'] ?? [],
                'qc_templates' => $data['qc_templates'] ?? [],
                'reports' => $data['reports'] ?? [],
                'print_templates' => $data['print_templates'] ?? [],
                'is_active' => (bool) ($data['is_active'] ?? false),
            ]
        );

        Cache::forget('industry_profile.active_id');
        Cache::forget('industry_profile.active');

        return $profile;
    }
}
