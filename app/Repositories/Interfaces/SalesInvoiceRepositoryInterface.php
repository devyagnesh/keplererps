<?php

namespace App\Repositories\Interfaces;

use App\Models\SalesInvoice;

interface SalesInvoiceRepositoryInterface
{
    public function findById(int $id): SalesInvoice;

    /** @param  array<string, mixed>  $data */
    public function create(array $data): SalesInvoice;

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): SalesInvoice;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
