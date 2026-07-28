<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\InspectionStatus;
use App\Enums\MaintenanceOrderStatus;
use App\Enums\PurchaseBillStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\SalesInvoiceStatus;
use App\Enums\SalesOrderStatus;
use App\Enums\WarehouseType;
use App\Enums\WorkOrderStatus;
use App\Models\DashboardRoleWidget;
use App\Models\MaintenanceOrder;
use App\Models\PurchaseBill;
use App\Models\PurchaseOrder;
use App\Models\QcInspection;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\StockBalance;
use App\Models\Warehouse;
use App\Models\WorkCentre;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Auth;

/**
 * Operational dashboard widgets (M15).
 *
 * Every widget is a single aggregate query so the landing page stays cheap to render.
 */
class DashboardService
{
    /**
     * Sales order statuses that still owe the customer a delivery.
     *
     * @var list<SalesOrderStatus>
     */
    protected const OPEN_SALES_ORDER_STATUSES = [
        SalesOrderStatus::Confirmed,
        SalesOrderStatus::PartiallyDelivered,
    ];

    /**
     * Purchase order statuses that still expect goods from the supplier.
     *
     * @var list<PurchaseOrderStatus>
     */
    protected const OPEN_PURCHASE_ORDER_STATUSES = [
        PurchaseOrderStatus::Approved,
        PurchaseOrderStatus::Sent,
        PurchaseOrderStatus::PartiallyReceived,
    ];

    /**
     * Work order statuses counted as live shop-floor load.
     *
     * @var list<WorkOrderStatus>
     */
    protected const LIVE_WORK_ORDER_STATUSES = [
        WorkOrderStatus::Released,
        WorkOrderStatus::InProgress,
    ];

    /**
     * Ageing buckets treated as past due, i.e. everything beyond the first 30 days.
     *
     * @var list<string>
     */
    protected const OVERDUE_BUCKETS = ['bucket_31_60', 'bucket_61_90', 'bucket_90_plus'];

    public function __construct(
        protected FinanceReportService $financeReports,
        protected SystemSettingService $settings,
        protected LeadService $leads
    ) {}

    /**
     * All dashboard widgets grouped by the module they belong to.
     *
     * @return array<string, array<string, mixed>>
     */
    public function widgets(): array
    {
        $widgets = [
            'sales' => $this->salesWidgets(),
            'purchase' => $this->purchaseWidgets(),
            'inventory' => $this->inventoryWidgets(),
            'production' => $this->productionWidgets(),
            'maintenance' => $this->maintenanceWidgets(),
            'finance' => $this->financeWidgets(),
        ];

        if ($this->settings->get('dashboard_show_pending_approvals', true)) {
            $widgets['approvals'] = $this->pendingApprovalWidgets();
        }

        if ($this->settings->get('dashboard_show_overdue_crm', true)) {
            $widgets['crm'] = [
                'overdue_follow_ups' => count($this->leads->overdueFollowUps()),
            ];
        }

        return $this->withDefaults($this->filterByRole($widgets));
    }

    /**
     * Ensure every widget group the dashboard view reads always exists.
     *
     * @param  array<string, array<string, mixed>>  $widgets
     * @return array<string, array<string, mixed>>
     */
    protected function withDefaults(array $widgets): array
    {
        $defaults = [
            'sales' => [
                'open_orders' => 0,
                'open_order_value' => 0.0,
                'invoiced_this_month' => 0.0,
                'overdue_deliveries' => 0,
            ],
            'purchase' => [
                'open_orders' => 0,
                'open_order_value' => 0.0,
                'bills_awaiting_approval' => 0,
                'bills_awaiting_value' => 0.0,
            ],
            'inventory' => [
                'quarantine_qty' => 0.0,
                'rejection_qty' => 0.0,
                'below_min_stock' => 0,
                'pending_inspections' => 0,
            ],
            'production' => [
                'live_orders' => 0,
                'due_orders' => 0,
                'planned_qty' => 0.0,
                'produced_qty' => 0.0,
            ],
            'maintenance' => [
                'under_breakdown' => 0,
                'open_orders' => 0,
                'pm_due' => 0,
                'downtime_minutes' => 0,
            ],
            'finance' => [
                'receivable_total' => 0.0,
                'receivable_overdue' => 0.0,
                'payable_total' => 0.0,
                'payable_overdue' => 0.0,
            ],
        ];

        foreach ($defaults as $group => $values) {
            $widgets[$group] = array_merge($values, $widgets[$group] ?? []);
        }

        return $widgets;
    }

    /**
     * Keep only widget groups configured for the current user's primary role.
     *
     * @param  array<string, array<string, mixed>>  $widgets
     * @return array<string, array<string, mixed>>
     */
    protected function filterByRole(array $widgets): array
    {
        $user = Auth::user();
        if ($user === null) {
            return $widgets;
        }

        $roleName = $user->roles()->orderBy('name')->value('name');
        if ($roleName === null || $roleName === 'Super Admin') {
            return $widgets;
        }

        $pack = DashboardRoleWidget::query()->where('role_name', $roleName)->first();
        if ($pack === null || empty($pack->widget_keys)) {
            return $widgets;
        }

        $allowed = array_flip($pack->widget_keys);

        return array_filter(
            $widgets,
            fn (string $key): bool => isset($allowed[$key]),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @return array{purchase_orders: int, sales_orders: int}
     */
    protected function pendingApprovalWidgets(): array
    {
        return [
            'purchase_orders' => PurchaseOrder::query()
                ->where('status', PurchaseOrderStatus::PendingApproval->value)
                ->count(),
            'sales_orders' => SalesOrder::query()
                ->where('status', SalesOrderStatus::PendingApproval->value)
                ->count(),
        ];
    }

    /**
     * @return array{open_orders: int, open_order_value: float, invoiced_this_month: float, quotation_pipeline: int}
     */
    protected function salesWidgets(): array
    {
        $openOrders = SalesOrder::query()->whereIn('status', $this->values(self::OPEN_SALES_ORDER_STATUSES));

        return [
            'open_orders' => (clone $openOrders)->count(),
            'open_order_value' => round((float) (clone $openOrders)->sum('grand_total'), 2),
            'invoiced_this_month' => round((float) SalesInvoice::query()
                ->where('status', SalesInvoiceStatus::Confirmed->value)
                ->whereDate('document_date', '>=', now()->startOfMonth()->toDateString())
                ->whereDate('document_date', '<=', now()->toDateString())
                ->sum('grand_total'), 2),
            'overdue_deliveries' => SalesOrder::query()
                ->whereIn('status', $this->values(self::OPEN_SALES_ORDER_STATUSES))
                ->whereNotNull('expected_delivery_date')
                ->whereDate('expected_delivery_date', '<', now()->toDateString())
                ->count(),
        ];
    }

    /**
     * @return array{open_orders: int, open_order_value: float, bills_awaiting_approval: int, bills_awaiting_value: float}
     */
    protected function purchaseWidgets(): array
    {
        $openOrders = PurchaseOrder::query()->whereIn('status', $this->values(self::OPEN_PURCHASE_ORDER_STATUSES));
        $draftBills = PurchaseBill::query()->where('status', PurchaseBillStatus::Draft->value);

        return [
            'open_orders' => (clone $openOrders)->count(),
            'open_order_value' => round((float) (clone $openOrders)->sum('grand_total'), 2),
            'bills_awaiting_approval' => (clone $draftBills)->count(),
            'bills_awaiting_value' => round((float) (clone $draftBills)->sum('grand_total'), 2),
        ];
    }

    /**
     * @return array{quarantine_qty: float, rejection_qty: float, below_min_stock: int, pending_inspections: int}
     */
    protected function inventoryWidgets(): array
    {
        return [
            'quarantine_qty' => $this->heldQty(WarehouseType::Quarantine),
            'rejection_qty' => $this->heldQty(WarehouseType::Rejection),
            'below_min_stock' => StockBalance::query()
                ->join('items', 'items.id', '=', 'stock_balances.item_id')
                ->whereNotNull('items.min_stock')
                ->where('items.min_stock', '>', 0)
                ->groupBy('stock_balances.item_id', 'items.min_stock')
                ->havingRaw('SUM(stock_balances.qty) < items.min_stock')
                ->select('stock_balances.item_id')
                ->get()
                ->count(),
            'pending_inspections' => QcInspection::query()
                ->whereIn('status', [InspectionStatus::Pending->value, InspectionStatus::InProgress->value])
                ->count(),
        ];
    }

    /**
     * @return array{live_orders: int, due_orders: int, planned_qty: float, produced_qty: float}
     */
    protected function productionWidgets(): array
    {
        $live = WorkOrder::query()->whereIn('status', $this->values(self::LIVE_WORK_ORDER_STATUSES));

        return [
            'live_orders' => (clone $live)->count(),
            'due_orders' => (clone $live)
                ->whereNotNull('planned_end_date')
                ->whereDate('planned_end_date', '<=', now()->toDateString())
                ->count(),
            'planned_qty' => round((float) (clone $live)->sum('planned_quantity'), 4),
            'produced_qty' => round((float) (clone $live)->sum('good_quantity'), 4),
        ];
    }

    /**
     * @return array{under_breakdown: int, open_orders: int, pm_due: int, downtime_minutes: int}
     */
    protected function maintenanceWidgets(): array
    {
        return [
            'under_breakdown' => WorkCentre::query()
                ->where('status', AssetStatus::UnderBreakdown->value)
                ->count(),
            'open_orders' => MaintenanceOrder::query()
                ->whereIn('status', [MaintenanceOrderStatus::Open->value, MaintenanceOrderStatus::InProgress->value])
                ->count(),
            'pm_due' => WorkCentre::query()
                ->where('is_active', true)
                ->whereNotNull('next_service_due_on')
                ->whereDate('next_service_due_on', '<=', now()->toDateString())
                ->count(),
            'downtime_minutes' => (int) MaintenanceOrder::query()
                ->whereDate('document_date', '>=', now()->startOfMonth()->toDateString())
                ->sum('downtime_minutes'),
        ];
    }

    /**
     * @return array{receivable_total: float, receivable_overdue: float, payable_total: float, payable_overdue: float}
     */
    protected function financeWidgets(): array
    {
        $receivable = $this->financeReports->ageing('receivable');
        $payable = $this->financeReports->ageing('payable');

        return [
            'receivable_total' => $this->ageingTotal($receivable, ['outstanding']),
            'receivable_overdue' => $this->ageingTotal($receivable, self::OVERDUE_BUCKETS),
            'payable_total' => $this->ageingTotal($payable, ['outstanding']),
            'payable_overdue' => $this->ageingTotal($payable, self::OVERDUE_BUCKETS),
        ];
    }

    /**
     * Quantity parked in warehouses of a given type, e.g. quarantine holds awaiting QC.
     */
    protected function heldQty(WarehouseType $type): float
    {
        return round((float) StockBalance::query()
            ->whereIn('warehouse_id', Warehouse::query()
                ->where('warehouse_type', $type->value)
                ->select('id'))
            ->sum('qty'), 4);
    }

    /**
     * Sum the given ageing columns across every party row.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $keys
     */
    protected function ageingTotal(array $rows, array $keys): float
    {
        $total = 0.0;

        foreach ($rows as $row) {
            foreach ($keys as $key) {
                $total += (float) ($row[$key] ?? 0);
            }
        }

        return round($total, 2);
    }

    /**
     * @param  list<\BackedEnum>  $cases
     * @return list<string>
     */
    protected function values(array $cases): array
    {
        return array_map(fn (\BackedEnum $case): string => (string) $case->value, $cases);
    }
}
