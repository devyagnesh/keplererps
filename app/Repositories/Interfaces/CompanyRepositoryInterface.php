<?php

namespace App\Repositories\Interfaces;

use App\Models\Company;
use Illuminate\Support\Collection;

/**
 * Data-access contract for the company singleton.
 */
interface CompanyRepositoryInterface
{
    /**
     * Return the single company row if it exists.
     */
    public function getSingleton(): ?Company;

    /**
     * Create the company singleton.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Company;

    /**
     * Update the company singleton.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Company $company, array $data): Company;

    /**
     * Active states for selects.
     *
     * @return Collection<int, \App\Models\State>
     */
    public function activeStates(): Collection;
}
