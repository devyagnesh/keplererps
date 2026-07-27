<?php

namespace App\Repositories\Interfaces;

/**
 * Data-access contract for stock balances and ledger inquiry.
 */
interface StockBalanceRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getBalancesForDataTable(array $params): array;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getLedgerForDataTable(array $params): array;

    /**
     * @return array{total_qty: float, total_value: float, lines: int}
     */
    public function valuationSummary(?int $warehouseId = null, ?int $categoryId = null): array;

    /**
     * Available-to-promise figures for an item, optionally scoped to one warehouse.
     *
     * @return array{
     *     item_id: int,
     *     warehouse_id: int|null,
     *     physical_qty: float,
     *     committed_qty: float,
     *     on_order_qty: float,
     *     free_qty: float
     * }
     */
    public function availability(int $itemId, ?int $warehouseId = null): array;
}
