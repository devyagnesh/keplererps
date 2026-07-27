<?php

namespace App\Repositories\Interfaces;

use App\Models\SalesQuotation;

interface SalesQuotationRepositoryInterface
{
    public function findById(int $id): SalesQuotation;

    /** @param  array<string, mixed>  $data */
    public function create(array $data): SalesQuotation;

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): SalesQuotation;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
