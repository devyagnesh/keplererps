<?php

namespace App\Services;

use App\Enums\CostingMethod;
use App\Repositories\Interfaces\SystemSettingRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * System settings business logic (M16 foundation).
 */
class SystemSettingService
{
    public function __construct(protected SystemSettingRepositoryInterface $repository) {}

    /** @return Collection<string, Collection<int, \App\Models\SystemSetting>> */
    public function grouped(): Collection
    {
        return $this->repository->allGrouped();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->repository->getValue($key, $default);
    }

    public function costingMethod(): CostingMethod
    {
        $value = (string) $this->get('costing_method', CostingMethod::WeightedAverage->value);

        return CostingMethod::tryFrom($value) ?? CostingMethod::WeightedAverage;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data): void
    {
        $costing = $this->repository->findByKey('costing_method');
        if (
            $costing !== null
            && array_key_exists('costing_method', $data)
            && (string) $data['costing_method'] !== (string) $costing->setting_value
            && $costing->is_locked
        ) {
            throw ValidationException::withMessages([
                'costing_method' => 'Costing method is locked after the first financial year close.',
            ]);
        }

        $this->repository->upsertMany($data);
    }
}
