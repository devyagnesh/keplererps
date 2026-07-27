<?php

namespace App\Services;

use App\Repositories\Interfaces\StockBalanceRepositoryInterface;

/**
 * Stock balance inquiry and valuation (US-M08-02).
 */
class StockBalanceService
{
    public function __construct(protected StockBalanceRepositoryInterface $repository) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function balanceDataTable(array $params): array
    {
        return $this->repository->getBalancesForDataTable($params);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function ledgerDataTable(array $params): array
    {
        return $this->repository->getLedgerForDataTable($params);
    }

    /**
     * @return array{total_qty: float, total_value: float, lines: int}
     */
    public function valuationSummary(?int $warehouseId = null, ?int $categoryId = null): array
    {
        return $this->repository->valuationSummary($warehouseId, $categoryId);
    }

    /**
     * Available-to-promise for an item (US-M03-04).
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
    public function availability(int $itemId, ?int $warehouseId = null): array
    {
        return $this->repository->availability($itemId, $warehouseId);
    }
}
