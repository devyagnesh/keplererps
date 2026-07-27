<?php

namespace App\Repositories\Eloquent;

use App\Models\Company;
use App\Models\State;
use App\Repositories\Interfaces\CompanyRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Eloquent implementation of CompanyRepositoryInterface.
 */
class CompanyRepository implements CompanyRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getSingleton(): ?Company
    {
        return Company::query()->with('state')->first();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Company
    {
        return Company::query()->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(Company $company, array $data): Company
    {
        $company->update($data);

        return $company->fresh(['state']);
    }

    /**
     * {@inheritdoc}
     */
    public function activeStates(): Collection
    {
        return State::query()
            ->select(['id', 'code', 'name'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
