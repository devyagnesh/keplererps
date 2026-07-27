<?php

namespace App\Repositories\Interfaces;

use App\Models\TaxRate;

/**
 * Data-access contract for tax rates.
 */
interface TaxRateRepositoryInterface
{
    public function findById(int $id): TaxRate;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TaxRate;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): TaxRate;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
