<?php

namespace App\Repositories\Eloquent;

use App\Enums\WarehouseType;
use App\Models\StockBalance;
use App\Models\StockLedgerEntry;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\StockBalanceRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Eloquent stock balance / ledger inquiry repository.
 */
class StockBalanceRepository implements StockBalanceRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function getBalancesForDataTable(array $params): array
    {
        $query = StockBalance::query()
            ->with(['item:id,item_code,item_name,category_id', 'warehouse:id,code,name,warehouse_type', 'batch:id,batch_no'])
            ->where('qty', '>', 0);

        if (! empty($params['warehouse_id'])) {
            $query->where('warehouse_id', $params['warehouse_id']);
        }
        if (! empty($params['item_id'])) {
            $query->where('item_id', $params['item_id']);
        }
        if (! empty($params['category_id'])) {
            $query->whereHas('item', fn (Builder $q) => $q->where('category_id', $params['category_id']));
        }

        return $this->buildDataTable(
            $query,
            ['id', 'item_id', 'warehouse_id', 'qty', 'value', 'updated_at'],
            [],
            function (StockBalance $row): array {
                $available = $row->warehouse?->warehouse_type === WarehouseType::Store
                    ? $row->availableQty()
                    : 0;

                return [
                    'id' => $row->id,
                    'item_code' => $row->item?->item_code ?? '—',
                    'item_name' => e($row->item?->item_name ?? '—'),
                    'warehouse' => $row->warehouse?->name ?? '—',
                    'batch' => $row->batch?->batch_no ?? '—',
                    'qty' => number_format((float) $row->qty, 4, '.', ''),
                    'committed_qty' => number_format((float) $row->committed_qty, 4, '.', ''),
                    'available_qty' => number_format((float) $available, 4, '.', ''),
                    'value' => number_format((float) $row->value, 2, '.', ''),
                ];
            },
            $params
        );
    }

    public function getLedgerForDataTable(array $params): array
    {
        $query = StockLedgerEntry::query()
            ->with(['item:id,item_code,item_name', 'warehouse:id,code,name', 'batch:id,batch_no']);

        if (! empty($params['warehouse_id'])) {
            $query->where('warehouse_id', $params['warehouse_id']);
        }
        if (! empty($params['item_id'])) {
            $query->where('item_id', $params['item_id']);
        }
        if (! empty($params['date_from'])) {
            $query->whereDate('posting_at', '>=', $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $query->whereDate('posting_at', '<=', $params['date_to']);
        }

        return $this->buildDataTable(
            $query,
            ['id', 'posting_at', 'item_id', 'warehouse_id', 'qty_in', 'qty_out', 'value', 'created_at'],
            [],
            function (StockLedgerEntry $entry): array {
                return [
                    'id' => $entry->id,
                    'posting_at' => $entry->posting_at?->format('Y-m-d H:i'),
                    'item' => ($entry->item?->item_code ?? '').' — '.e($entry->item?->item_name ?? ''),
                    'warehouse' => $entry->warehouse?->name ?? '—',
                    'batch' => $entry->batch?->batch_no ?? '—',
                    'type' => $entry->transaction_type->label(),
                    'qty_in' => number_format((float) $entry->qty_in, 4, '.', ''),
                    'qty_out' => number_format((float) $entry->qty_out, 4, '.', ''),
                    'rate' => number_format((float) $entry->rate, 4, '.', ''),
                    'value' => number_format((float) $entry->value, 2, '.', ''),
                    'balance_qty' => number_format((float) $entry->balance_qty, 4, '.', ''),
                ];
            },
            $params
        );
    }

    public function valuationSummary(?int $warehouseId = null, ?int $categoryId = null): array
    {
        $query = StockBalance::query()
            ->where('qty', '>', 0)
            ->when($warehouseId, fn (Builder $q) => $q->where('warehouse_id', $warehouseId))
            ->when($categoryId, fn (Builder $q) => $q->whereHas('item', fn (Builder $inner) => $inner->where('category_id', $categoryId)));

        $row = $query->selectRaw(
            'COALESCE(SUM(qty),0) as total_qty, COALESCE(SUM(value),0) as total_value, COUNT(*) as line_count'
        )->first();

        return [
            'total_qty' => (float) ($row->total_qty ?? 0),
            'total_value' => (float) ($row->total_value ?? 0),
            'lines' => (int) ($row->line_count ?? 0),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function availability(int $itemId, ?int $warehouseId = null): array
    {
        // Only store warehouses count toward promised/free stock; quarantine, WIP and
        // rejection stock is physically present but not sellable (SRS A3).
        $row = StockBalance::query()
            ->where('stock_balances.item_id', $itemId)
            ->whereHas('warehouse', function (Builder $q) use ($warehouseId): void {
                $q->where('warehouse_type', WarehouseType::Store->value)
                    ->when($warehouseId, fn (Builder $inner) => $inner->where('id', $warehouseId));
            })
            ->selectRaw('COALESCE(SUM(qty),0) as physical_qty')
            ->selectRaw('COALESCE(SUM(committed_qty),0) as committed_qty')
            ->selectRaw('COALESCE(SUM(on_order_qty),0) as on_order_qty')
            ->first();

        $physical = round((float) ($row->physical_qty ?? 0), 4);
        $committed = round((float) ($row->committed_qty ?? 0), 4);
        $onOrder = round((float) ($row->on_order_qty ?? 0), 4);

        return [
            'item_id' => $itemId,
            'warehouse_id' => $warehouseId,
            'physical_qty' => $physical,
            'committed_qty' => $committed,
            'on_order_qty' => $onOrder,
            'free_qty' => round($physical - $committed, 4),
        ];
    }
}
