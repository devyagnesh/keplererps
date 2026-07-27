<?php

namespace App\Services;

use App\Models\ProductionEntry;
use App\Models\PurchaseBill;
use App\Models\PurchaseIndentItem;
use App\Models\PurchaseOrder;
use App\Models\QcInspection;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\StockLedgerEntry;
use App\Models\GoodsReceipt;
use App\Enums\InspectionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Cross-module register reports for a date range (M15).
 *
 * Each register returns `{rows, totals, columns}` so one Blade view and one JS file can render
 * any register, and the same rows feed the CSV export.
 */
class RegisterReportService
{
    /**
     * Row cap so a wide date range cannot exhaust memory; the UI warns when it is hit.
     */
    public const MAX_ROWS = 5000;

    /**
     * Sales invoice register.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: list<array<string, mixed>>, totals: array<string, float>}
     */
    public function sales(string $fromDate, string $toDate, array $filters = []): array
    {
        $invoices = SalesInvoice::query()
            ->with(['customer:id,party_code,party_name', 'items'])
            ->whereDate('document_date', '>=', $fromDate)
            ->whereDate('document_date', '<=', $toDate)
            ->when(! empty($filters['party_id']), fn (Builder $q) => $q->where('customer_id', (int) $filters['party_id']))
            ->when(! empty($filters['status']), fn (Builder $q) => $q->where('status', $filters['status']))
            ->orderBy('document_date')
            ->orderBy('id')
            ->limit(self::MAX_ROWS)
            ->get();

        $rows = $invoices->map(fn (SalesInvoice $invoice): array => [
            'document_no' => $invoice->document_no,
            'document_date' => $invoice->document_date?->toDateString(),
            'party_code' => $invoice->customer?->party_code,
            'party_name' => $invoice->customer?->party_name,
            'taxable_value' => round((float) $invoice->items->sum(fn ($line) => (float) $line->taxable_amount), 2),
            'tax_amount' => round((float) $invoice->items->sum(
                fn ($line) => (float) $line->cgst_amount + (float) $line->sgst_amount + (float) $line->igst_amount
            ), 2),
            'invoice_value' => round((float) $invoice->grand_total, 2),
            'status' => $invoice->status->label(),
        ])->all();

        return [
            'rows' => $rows,
            'totals' => $this->sumColumns($rows, ['taxable_value', 'tax_amount', 'invoice_value']),
        ];
    }

    /**
     * Purchase bill register.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: list<array<string, mixed>>, totals: array<string, float>}
     */
    public function purchase(string $fromDate, string $toDate, array $filters = []): array
    {
        $bills = PurchaseBill::query()
            ->with(['supplier:id,party_code,party_name', 'items'])
            ->whereDate('document_date', '>=', $fromDate)
            ->whereDate('document_date', '<=', $toDate)
            ->when(! empty($filters['party_id']), fn (Builder $q) => $q->where('supplier_id', (int) $filters['party_id']))
            ->when(! empty($filters['status']), fn (Builder $q) => $q->where('status', $filters['status']))
            ->orderBy('document_date')
            ->orderBy('id')
            ->limit(self::MAX_ROWS)
            ->get();

        $rows = $bills->map(fn (PurchaseBill $bill): array => [
            'document_no' => $bill->document_no,
            'document_date' => $bill->document_date?->toDateString(),
            'supplier_bill_no' => $bill->supplier_bill_no,
            'party_code' => $bill->supplier?->party_code,
            'party_name' => $bill->supplier?->party_name,
            'taxable_value' => round((float) $bill->items->sum(fn ($line) => (float) $line->taxable_amount), 2),
            'tax_amount' => round((float) $bill->items->sum(fn ($line) => (float) $line->tax_amount), 2),
            'bill_value' => round((float) $bill->grand_total, 2),
            'status' => $bill->status->label(),
        ])->all();

        return [
            'rows' => $rows,
            'totals' => $this->sumColumns($rows, ['taxable_value', 'tax_amount', 'bill_value']),
        ];
    }

    /**
     * Item and warehouse wise opening / inward / outward / closing quantities.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: list<array<string, mixed>>, totals: array<string, float>}
     */
    public function stock(string $fromDate, string $toDate, array $filters = []): array
    {
        $openings = $this->stockAggregate(
            $this->stockQuery($filters)->whereDate('posting_at', '<', $fromDate)
        );

        $movements = $this->stockAggregate(
            $this->stockQuery($filters)
                ->whereDate('posting_at', '>=', $fromDate)
                ->whereDate('posting_at', '<=', $toDate)
        );

        $labels = $this->stockLabels(array_keys($openings + $movements));
        $rows = [];

        foreach ($labels as $key => $label) {
            $opening = round((float) (($openings[$key]['in'] ?? 0) - ($openings[$key]['out'] ?? 0)), 4);
            $inward = round((float) ($movements[$key]['in'] ?? 0), 4);
            $outward = round((float) ($movements[$key]['out'] ?? 0), 4);

            if ($opening === 0.0 && $inward === 0.0 && $outward === 0.0) {
                continue;
            }

            $rows[] = [
                'item_code' => $label['item_code'],
                'item_name' => $label['item_name'],
                'warehouse' => $label['warehouse'],
                'uom' => $label['uom'],
                'opening_qty' => $opening,
                'inward_qty' => $inward,
                'outward_qty' => $outward,
                'closing_qty' => round($opening + $inward - $outward, 4),
            ];
        }

        usort($rows, fn (array $a, array $b): int => [$a['item_code'], $a['warehouse']] <=> [$b['item_code'], $b['warehouse']]);

        return [
            'rows' => $rows,
            'totals' => $this->sumColumns($rows, ['opening_qty', 'inward_qty', 'outward_qty', 'closing_qty']),
        ];
    }

    /**
     * Production entry register with good, rejected and downtime figures.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: list<array<string, mixed>>, totals: array<string, float>}
     */
    public function production(string $fromDate, string $toDate, array $filters = []): array
    {
        $entries = ProductionEntry::query()
            ->with([
                'workOrder:id,document_no,item_id,work_centre_id',
                'workOrder.item:id,item_code,item_name',
                'workOrder.workCentre:id,code,name',
            ])
            ->whereNotNull('posted_at')
            ->whereDate('document_date', '>=', $fromDate)
            ->whereDate('document_date', '<=', $toDate)
            ->when(! empty($filters['item_id']), fn (Builder $q) => $q->whereHas(
                'workOrder',
                fn (Builder $inner) => $inner->where('item_id', (int) $filters['item_id'])
            ))
            ->when(! empty($filters['work_centre_id']), fn (Builder $q) => $q->whereHas(
                'workOrder',
                fn (Builder $inner) => $inner->where('work_centre_id', (int) $filters['work_centre_id'])
            ))
            ->orderBy('document_date')
            ->orderBy('id')
            ->limit(self::MAX_ROWS)
            ->get();

        $rows = $entries->map(function (ProductionEntry $entry): array {
            $good = round((float) $entry->good_quantity, 4);
            $rejected = round((float) $entry->rejected_quantity, 4);
            $produced = $good + $rejected;

            return [
                'document_no' => $entry->document_no,
                'document_date' => $entry->document_date?->toDateString(),
                'work_order_no' => $entry->workOrder?->document_no,
                'item_code' => $entry->workOrder?->item?->item_code,
                'item_name' => $entry->workOrder?->item?->item_name,
                'work_centre' => $entry->workOrder?->workCentre?->code,
                'good_qty' => $good,
                'rejected_qty' => $rejected,
                'rejection_pct' => $produced > 0 ? round(($rejected / $produced) * 100, 2) : 0.0,
                'downtime_minutes' => (int) $entry->downtime_minutes,
                'total_cost' => round((float) $entry->total_cost, 2),
            ];
        })->all();

        return [
            'rows' => $rows,
            'totals' => $this->sumColumns($rows, ['good_qty', 'rejected_qty', 'downtime_minutes', 'total_cost']),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<StockLedgerEntry>
     */
    protected function stockQuery(array $filters): Builder
    {
        return StockLedgerEntry::query()
            ->when(! empty($filters['item_id']), fn (Builder $q) => $q->where('item_id', (int) $filters['item_id']))
            ->when(! empty($filters['warehouse_id']), fn (Builder $q) => $q->where('warehouse_id', (int) $filters['warehouse_id']));
    }

    /**
     * Inward and outward totals keyed by `item_id:warehouse_id`.
     *
     * @param  Builder<StockLedgerEntry>  $query
     * @return array<string, array{in: float, out: float}>
     */
    protected function stockAggregate(Builder $query): array
    {
        return $query
            ->select('item_id', 'warehouse_id')
            ->selectRaw('COALESCE(SUM(qty_in), 0) as qty_in_total')
            ->selectRaw('COALESCE(SUM(qty_out), 0) as qty_out_total')
            ->groupBy('item_id', 'warehouse_id')
            ->get()
            ->mapWithKeys(fn ($row): array => [
                $row->item_id.':'.$row->warehouse_id => [
                    'in' => (float) $row->qty_in_total,
                    'out' => (float) $row->qty_out_total,
                ],
            ])
            ->all();
    }

    /**
     * Resolve item / warehouse labels for the aggregated keys in one query pair.
     *
     * @param  list<string>  $keys
     * @return array<string, array{item_code: ?string, item_name: ?string, warehouse: ?string, uom: ?string}>
     */
    protected function stockLabels(array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        $itemIds = [];
        $warehouseIds = [];
        foreach ($keys as $key) {
            [$itemId, $warehouseId] = explode(':', $key);
            $itemIds[(int) $itemId] = true;
            $warehouseIds[(int) $warehouseId] = true;
        }

        $items = DB::table('items')
            ->leftJoin('uoms', 'uoms.id', '=', 'items.stock_uom_id')
            ->whereIn('items.id', array_keys($itemIds))
            ->get(['items.id', 'items.item_code', 'items.item_name', 'uoms.code as uom_code'])
            ->keyBy('id');

        $warehouses = DB::table('warehouses')
            ->whereIn('id', array_keys($warehouseIds))
            ->get(['id', 'code'])
            ->keyBy('id');

        $labels = [];
        foreach ($keys as $key) {
            [$itemId, $warehouseId] = explode(':', $key);
            $item = $items->get((int) $itemId);

            $labels[$key] = [
                'item_code' => $item?->item_code,
                'item_name' => $item?->item_name,
                'warehouse' => $warehouses->get((int) $warehouseId)?->code,
                'uom' => $item?->uom_code,
            ];
        }

        return $labels;
    }

    /**
     * Production rejection summary by defect reason (M15).
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: list<array<string, mixed>>, totals: array<string, float>}
     */
    public function rejection(string $fromDate, string $toDate, array $filters = []): array
    {
        $entries = ProductionEntry::query()
            ->with(['defectReason:id,code,name', 'workOrder:id,document_no,item_id', 'workOrder.item:id,item_code,item_name'])
            ->whereNotNull('posted_at')
            ->where('rejected_quantity', '>', 0)
            ->whereDate('document_date', '>=', $fromDate)
            ->whereDate('document_date', '<=', $toDate)
            ->when(! empty($filters['item_id']), fn (Builder $q) => $q->whereHas(
                'workOrder',
                fn (Builder $inner) => $inner->where('item_id', (int) $filters['item_id'])
            ))
            ->orderBy('document_date')
            ->limit(self::MAX_ROWS)
            ->get();

        $rows = $entries->map(fn (ProductionEntry $entry): array => [
            'document_no' => $entry->document_no,
            'document_date' => $entry->document_date?->toDateString(),
            'work_order_no' => $entry->workOrder?->document_no,
            'item_code' => $entry->workOrder?->item?->item_code,
            'item_name' => $entry->workOrder?->item?->item_name,
            'defect_code' => $entry->defectReason?->code,
            'defect_name' => $entry->defectReason?->name,
            'rejected_qty' => round((float) $entry->rejected_quantity, 4),
        ])->all();

        return [
            'rows' => $rows,
            'totals' => $this->sumColumns($rows, ['rejected_qty']),
        ];
    }

    /**
     * Pending sales orders (confirmed, not fully delivered/invoiced).
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: list<array<string, mixed>>, totals: array<string, float>}
     */
    public function pendingSalesOrders(string $fromDate, string $toDate, array $filters = []): array
    {
        $orders = \App\Models\SalesOrder::query()
            ->with(['customer:id,party_code,party_name', 'items'])
            ->whereIn('status', ['confirmed', 'partially_delivered'])
            ->whereDate('document_date', '>=', $fromDate)
            ->whereDate('document_date', '<=', $toDate)
            ->when(! empty($filters['party_id']), fn (Builder $q) => $q->where('customer_id', (int) $filters['party_id']))
            ->orderBy('document_date')
            ->limit(self::MAX_ROWS)
            ->get();

        $rows = $orders->map(function ($order): array {
            $ordered = (float) $order->items->sum('quantity');
            $delivered = (float) $order->items->sum('delivered_qty');

            return [
                'document_no' => $order->document_no,
                'document_date' => $order->document_date?->toDateString(),
                'party_code' => $order->customer?->party_code,
                'party_name' => $order->customer?->party_name,
                'ordered_qty' => round($ordered, 4),
                'delivered_qty' => round($delivered, 4),
                'pending_qty' => round(max(0, $ordered - $delivered), 4),
                'grand_total' => round((float) $order->grand_total, 2),
                'status' => $order->status?->label() ?? $order->status,
            ];
        })->all();

        return [
            'rows' => $rows,
            'totals' => $this->sumColumns($rows, ['ordered_qty', 'delivered_qty', 'pending_qty', 'grand_total']),
        ];
    }

    /**
     * Pending purchase orders awaiting receipt.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: list<array<string, mixed>>, totals: array<string, float>}
     */
    public function pendingPurchaseOrders(string $fromDate, string $toDate, array $filters = []): array
    {
        $orders = PurchaseOrder::query()
            ->with(['supplier:id,party_code,party_name', 'items'])
            ->whereIn('status', ['approved', 'sent', 'partially_received'])
            ->whereDate('document_date', '>=', $fromDate)
            ->whereDate('document_date', '<=', $toDate)
            ->when(! empty($filters['party_id']), fn (Builder $q) => $q->where('supplier_id', (int) $filters['party_id']))
            ->orderBy('document_date')
            ->limit(self::MAX_ROWS)
            ->get();

        $rows = $orders->map(function (PurchaseOrder $order): array {
            $ordered = (float) $order->items->sum('quantity');
            $received = (float) $order->items->sum(fn ($line) => (float) ($line->received_qty ?? 0));

            return [
                'document_no' => $order->document_no,
                'document_date' => $order->document_date?->toDateString(),
                'party_code' => $order->supplier?->party_code,
                'party_name' => $order->supplier?->party_name,
                'ordered_qty' => round($ordered, 4),
                'received_qty' => round($received, 4),
                'pending_qty' => round(max(0, $ordered - $received), 4),
                'grand_total' => round((float) $order->grand_total, 2),
                'status' => $order->status?->label() ?? $order->status,
            ];
        })->all();

        return [
            'rows' => $rows,
            'totals' => $this->sumColumns($rows, ['ordered_qty', 'received_qty', 'pending_qty', 'grand_total']),
        ];
    }

    /**
     * Stock valuation from current balances × average/standard rate.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: list<array<string, mixed>>, totals: array<string, float>}
     */
    public function stockValuation(string $fromDate, string $toDate, array $filters = []): array
    {
        unset($fromDate, $toDate);

        $balances = \App\Models\StockBalance::query()
            ->with(['item:id,item_code,item_name,standard_cost', 'warehouse:id,code,name'])
            ->where('qty', '!=', 0)
            ->when(! empty($filters['item_id']), fn (Builder $q) => $q->where('item_id', (int) $filters['item_id']))
            ->when(! empty($filters['warehouse_id']), fn (Builder $q) => $q->where('warehouse_id', (int) $filters['warehouse_id']))
            ->orderBy('item_id')
            ->limit(self::MAX_ROWS)
            ->get();

        $rows = $balances->map(function ($balance): array {
            $qty = round((float) $balance->qty, 4);
            $value = round((float) $balance->value, 2);
            $rate = $qty != 0.0 ? round($value / $qty, 4) : round((float) ($balance->item?->standard_cost ?? 0), 4);

            return [
                'item_code' => $balance->item?->item_code,
                'item_name' => $balance->item?->item_name,
                'warehouse' => $balance->warehouse?->code,
                'qty_on_hand' => $qty,
                'rate' => $rate,
                'value' => $value,
            ];
        })->all();

        return [
            'rows' => $rows,
            'totals' => $this->sumColumns($rows, ['qty_on_hand', 'value']),
        ];
    }

    /**
     * Slow / dead stock based on last movement vs configured slow_moving_days.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: list<array<string, mixed>>, totals: array<string, float>}
     */
    public function slowMoving(string $fromDate, string $toDate, array $filters = []): array
    {
        unset($fromDate, $toDate);
        $days = (int) (app(SystemSettingService::class)->get('slow_moving_days', 90));
        $cutoff = now()->subDays(max(1, $days))->toDateString();

        $balances = \App\Models\StockBalance::query()
            ->with(['item:id,item_code,item_name', 'warehouse:id,code,name'])
            ->where('qty', '>', 0)
            ->when(! empty($filters['warehouse_id']), fn (Builder $q) => $q->where('warehouse_id', (int) $filters['warehouse_id']))
            ->limit(self::MAX_ROWS)
            ->get();

        $rows = [];
        foreach ($balances as $balance) {
            $last = StockLedgerEntry::query()
                ->where('item_id', $balance->item_id)
                ->where('warehouse_id', $balance->warehouse_id)
                ->max('posting_at');

            if ($last !== null && $last >= $cutoff) {
                continue;
            }

            $rows[] = [
                'item_code' => $balance->item?->item_code,
                'item_name' => $balance->item?->item_name,
                'warehouse' => $balance->warehouse?->code,
                'qty_on_hand' => round((float) $balance->qty, 4),
                'last_movement' => $last ? substr((string) $last, 0, 10) : 'never',
                'days_idle' => $last ? now()->diffInDays(\Illuminate\Support\Carbon::parse($last)) : $days,
            ];
        }

        return [
            'rows' => $rows,
            'totals' => $this->sumColumns($rows, ['qty_on_hand']),
        ];
    }

    /**
     * Day book of posted journal vouchers in the period.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: list<array<string, mixed>>, totals: array<string, float>}
     */
    public function dayBook(string $fromDate, string $toDate, array $filters = []): array
    {
        $vouchers = \App\Models\JournalVoucher::query()
            ->with(['lines.ledgerAccount:id,code,name'])
            ->whereDate('document_date', '>=', $fromDate)
            ->whereDate('document_date', '<=', $toDate)
            ->where('status', \App\Enums\DocumentStatus::Posted)
            ->when(! empty($filters['voucher_type']), fn (Builder $q) => $q->where('voucher_type', $filters['voucher_type']))
            ->orderBy('document_date')
            ->orderBy('id')
            ->limit(self::MAX_ROWS)
            ->get();

        $rows = [];
        foreach ($vouchers as $voucher) {
            foreach ($voucher->lines as $line) {
                $rows[] = [
                    'document_no' => $voucher->document_no,
                    'document_date' => $voucher->document_date?->toDateString(),
                    'voucher_type' => $voucher->voucher_type->label(),
                    'account' => trim(($line->ledgerAccount?->code ?? '').' '.($line->ledgerAccount?->name ?? '')),
                    'debit' => round((float) $line->debit, 2),
                    'credit' => round((float) $line->credit, 2),
                    'narration' => $line->narration ?: $voucher->narration,
                ];
            }
        }

        return [
            'rows' => $rows,
            'totals' => $this->sumColumns($rows, ['debit', 'credit']),
        ];
    }

    /**
     * Sales performance grouped by assigned sales executive.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: list<array<string, mixed>>, totals: array<string, float>}
     */
    public function salesExecutive(string $fromDate, string $toDate, array $filters = []): array
    {
        $invoices = SalesInvoice::query()
            ->with([
                'customer:id,party_code,party_name,assigned_user_id',
                'customer.assignedUser:id,name',
                'salesOrder:id,created_by',
            ])
            ->whereDate('document_date', '>=', $fromDate)
            ->whereDate('document_date', '<=', $toDate)
            ->when(! empty($filters['party_id']), fn (Builder $q) => $q->where('customer_id', (int) $filters['party_id']))
            ->orderBy('document_date')
            ->limit(self::MAX_ROWS)
            ->get(['id', 'document_date', 'customer_id', 'sales_order_id', 'grand_total', 'created_by']);

        $userIds = $invoices->flatMap(fn (SalesInvoice $invoice): array => array_filter([
            $invoice->customer?->assigned_user_id,
            $invoice->salesOrder?->created_by,
            $invoice->created_by,
        ]))->unique()->values();

        $orders = SalesOrder::query()
            ->with(['customer:id,assigned_user_id', 'customer.assignedUser:id,name'])
            ->whereDate('document_date', '>=', $fromDate)
            ->whereDate('document_date', '<=', $toDate)
            ->when(! empty($filters['party_id']), fn (Builder $q) => $q->where('customer_id', (int) $filters['party_id']))
            ->limit(self::MAX_ROWS)
            ->get(['id', 'customer_id', 'created_by']);

        $userIds = $userIds->merge($orders->flatMap(fn (SalesOrder $order): array => array_filter([
            $order->customer?->assigned_user_id,
            $order->created_by,
        ])))->unique()->filter();

        $userNames = User::query()->whereIn('id', $userIds)->pluck('name', 'id');

        $grouped = [];

        foreach ($invoices as $invoice) {
            $executiveId = $invoice->customer?->assigned_user_id
                ?? $invoice->salesOrder?->created_by
                ?? $invoice->created_by;
            $executiveName = $invoice->customer?->assignedUser?->name
                ?? ($executiveId ? ($userNames[(int) $executiveId] ?? 'Unknown') : 'Unassigned');
            $key = (string) ($executiveId ?? 'unassigned');

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'executive' => $executiveName,
                    'invoice_count' => 0,
                    'invoice_value' => 0.0,
                    'customer_count' => [],
                ];
            }

            $grouped[$key]['invoice_count']++;
            $grouped[$key]['invoice_value'] += (float) $invoice->grand_total;
            $grouped[$key]['customer_count'][(int) $invoice->customer_id] = true;
        }

        foreach ($orders as $order) {
            $executiveId = $order->customer?->assigned_user_id ?? $order->created_by;
            $executiveName = $order->customer?->assignedUser?->name
                ?? ($executiveId ? ($userNames[(int) $executiveId] ?? 'Unknown') : 'Unassigned');
            $key = (string) ($executiveId ?? 'unassigned');

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'executive' => $executiveName,
                    'invoice_count' => 0,
                    'invoice_value' => 0.0,
                    'customer_count' => [],
                ];
            }

            $grouped[$key]['customer_count'][(int) $order->customer_id] = true;
        }

        $rows = collect($grouped)
            ->map(fn (array $row): array => [
                'executive' => $row['executive'],
                'invoice_count' => $row['invoice_count'],
                'invoice_value' => round($row['invoice_value'], 2),
                'customer_count' => count($row['customer_count']),
            ])
            ->sortByDesc('invoice_value')
            ->values()
            ->all();

        return [
            'rows' => $rows,
            'totals' => $this->sumColumns($rows, ['invoice_count', 'invoice_value', 'customer_count']),
        ];
    }

    /**
     * Purchase indent lines with ordered vs indent quantity.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: list<array<string, mixed>>, totals: array<string, float>}
     */
    public function purchaseVsIndent(string $fromDate, string $toDate, array $filters = []): array
    {
        $lines = PurchaseIndentItem::query()
            ->with([
                'indent:id,document_no,document_date,status',
                'item:id,item_code,item_name',
                'uom:id,code',
            ])
            ->whereHas('indent', fn (Builder $q) => $q
                ->whereDate('document_date', '>=', $fromDate)
                ->whereDate('document_date', '<=', $toDate))
            ->orderBy('purchase_indent_id')
            ->limit(self::MAX_ROWS)
            ->get();

        $rows = $lines->map(function (PurchaseIndentItem $line): array {
            $indentQty = round((float) $line->quantity, 4);
            $orderedQty = round((float) $line->ordered_qty, 4);

            return [
                'indent_no' => $line->indent?->document_no,
                'indent_date' => $line->indent?->document_date?->toDateString(),
                'item_code' => $line->item?->item_code,
                'item_name' => $line->item?->item_name,
                'uom' => $line->uom?->code,
                'indent_qty' => $indentQty,
                'ordered_qty' => $orderedQty,
                'pending_qty' => round(max(0, $indentQty - $orderedQty), 4),
                'status' => $line->indent?->status?->label() ?? $line->indent?->status,
            ];
        })->all();

        return [
            'rows' => $rows,
            'totals' => $this->sumColumns($rows, ['indent_qty', 'ordered_qty', 'pending_qty']),
        ];
    }

    /**
     * GRN-linked QC inspections still awaiting completion.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: list<array<string, mixed>>, totals: array<string, float>}
     */
    public function grnPendingInspection(string $fromDate, string $toDate, array $filters = []): array
    {
        $inspections = QcInspection::query()
            ->with([
                'source',
                'item:id,item_code,item_name',
                'inspector:id,name',
            ])
            ->whereIn('status', [InspectionStatus::Pending, InspectionStatus::InProgress])
            ->where('source_type', GoodsReceipt::class)
            ->whereDate('document_date', '>=', $fromDate)
            ->whereDate('document_date', '<=', $toDate)
            ->when(! empty($filters['item_id']), fn (Builder $q) => $q->where('item_id', (int) $filters['item_id']))
            ->orderBy('document_date')
            ->limit(self::MAX_ROWS)
            ->get();

        $rows = $inspections->map(function (QcInspection $inspection): array {
            /** @var GoodsReceipt|null $grn */
            $grn = $inspection->source;

            return [
                'inspection_no' => $inspection->document_no,
                'inspection_date' => $inspection->document_date?->toDateString(),
                'grn_no' => $grn?->document_no,
                'grn_date' => $grn?->document_date?->toDateString(),
                'item_code' => $inspection->item?->item_code,
                'item_name' => $inspection->item?->item_name,
                'lot_quantity' => round((float) $inspection->lot_quantity, 4),
                'status' => $inspection->status->label(),
                'inspector' => $inspection->inspector?->name,
            ];
        })->all();

        return [
            'rows' => $rows,
            'totals' => $this->sumColumns($rows, ['lot_quantity']),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $columns
     * @return array<string, float>
     */
    protected function sumColumns(array $rows, array $columns): array
    {
        $totals = [];

        foreach ($columns as $column) {
            $totals[$column] = round(array_sum(array_map(
                fn (array $row): float => (float) ($row[$column] ?? 0),
                $rows
            )), 4);
        }

        return $totals;
    }
}
