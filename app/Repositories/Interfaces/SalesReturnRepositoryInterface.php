<?php

namespace App\Repositories\Interfaces;

use App\Models\SalesReturn;

/**
 * Sales return data-access contract.
 */
interface SalesReturnRepositoryInterface
{
    public function findById(int $id): SalesReturn;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SalesReturn;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): SalesReturn;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;

    /**
     * Quantity already returned per invoice line, excluding cancelled returns.
     *
     * @param  list<int>  $salesInvoiceItemIds
     * @return array<int, float>
     */
    public function returnedQtyByInvoiceItem(array $salesInvoiceItemIds, ?int $ignoreReturnId = null): array;
}
