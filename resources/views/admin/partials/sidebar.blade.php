<aside class="app-sidebar sticky" id="sidebar">
    <div class="main-sidebar-header">
        <a href="{{ route('admin.dashboard') }}" class="header-logo">
            <img src="{{ asset('assets/admin/images/brand-logos/desktop-logo.png') }}" alt="logo" class="desktop-logo">
            <img src="{{ asset('assets/admin/images/brand-logos/toggle-logo.png') }}" alt="logo" class="toggle-logo">
            <img src="{{ asset('assets/admin/images/brand-logos/desktop-dark.png') }}" alt="logo" class="desktop-dark">
            <img src="{{ asset('assets/admin/images/brand-logos/toggle-dark.png') }}" alt="logo" class="toggle-dark">
            <img src="{{ asset('assets/admin/images/brand-logos/desktop-white.png') }}" alt="logo" class="desktop-white">
            <img src="{{ asset('assets/admin/images/brand-logos/toggle-white.png') }}" alt="logo" class="toggle-white">
        </a>
    </div>
    <div class="main-sidebar" id="sidebar-scroll">
        <nav class="main-menu-container nav nav-pills flex-column sub-open">
            <ul class="main-menu">
                <li class="slide__category"><span class="category-name">Main</span></li>
                <li class="slide">
                    <a href="{{ route('admin.dashboard') }}" class="side-menu__item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="bx bx-home side-menu__icon"></i>
                        <span class="side-menu__label">Dashboard</span>
                    </a>
                </li>
                @if (auth()->user()?->hasAnyPermission([
                    'company.view', 'branch.view', 'warehouse.view', 'party.view', 'tax_rate.view',
                    'uom.view', 'category.view', 'transporter.view', 'hsn_code.view', 'item.view', 'bom.view',
                ]))
                <li class="slide__category"><span class="category-name">Master Data</span></li>
                <li class="slide has-sub {{ request()->routeIs('admin.company.*','admin.branches.*','admin.warehouses.*','admin.parties.*','admin.tax-rates.*','admin.uoms.*','admin.categories.*','admin.transporters.*','admin.hsn-codes.*','admin.items.*','admin.boms.*') ? 'open' : '' }}">
                    <a href="javascript:void(0);" class="side-menu__item">
                        <i class="bx bx-layer side-menu__icon"></i>
                        <span class="side-menu__label">Company & Masters</span>
                        <i class="fe fe-chevron-right side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1" style="{{ request()->routeIs('admin.company.*','admin.branches.*','admin.warehouses.*','admin.parties.*','admin.tax-rates.*','admin.uoms.*','admin.categories.*','admin.transporters.*','admin.hsn-codes.*','admin.items.*','admin.boms.*') ? 'display:block' : '' }}">
                        @can('company.view')<li class="slide"><a href="{{ route('admin.company.edit') }}" class="side-menu__item">Company Setup</a></li>@endcan
                        @can('branch.view')<li class="slide"><a href="{{ route('admin.branches.index') }}" class="side-menu__item">Branches</a></li>@endcan
                        @can('warehouse.view')<li class="slide"><a href="{{ route('admin.warehouses.index') }}" class="side-menu__item">Warehouses</a></li>@endcan
                        @can('party.view')
                            <li class="slide"><a href="{{ route('admin.parties.index') }}" class="side-menu__item">Customers & Suppliers</a></li>
                            <li class="slide"><a href="{{ route('admin.parties.import.index') }}" class="side-menu__item">Import Parties</a></li>
                        @endcan
                        @can('tax_rate.view')<li class="slide"><a href="{{ route('admin.tax-rates.index') }}" class="side-menu__item">Tax Rates</a></li>@endcan
                        @can('uom.view')<li class="slide"><a href="{{ route('admin.uoms.index') }}" class="side-menu__item">UOM</a></li>@endcan
                        @can('category.view')<li class="slide"><a href="{{ route('admin.categories.index') }}" class="side-menu__item">Categories</a></li>@endcan
                        @can('transporter.view')<li class="slide"><a href="{{ route('admin.transporters.index') }}" class="side-menu__item">Transporters</a></li>@endcan
                        @can('hsn_code.view')<li class="slide"><a href="{{ route('admin.hsn-codes.index') }}" class="side-menu__item">HSN / SAC</a></li>@endcan
                        @can('item.view')<li class="slide"><a href="{{ route('admin.items.index') }}" class="side-menu__item">Items</a></li>@endcan
                        @can('bom.view')<li class="slide"><a href="{{ route('admin.boms.index') }}" class="side-menu__item">Bill of Materials</a></li>@endcan
                    </ul>
                </li>
                @endif
                @if (auth()->user()?->hasAnyPermission([
                    'purchase_order.view', 'goods_receipt.view', 'purchase_suggestion.view', 'purchase_indent.view', 'purchase_rfq.view', 'purchase_bill.view', 'purchase_return.view', 'supplier_rating.view',
                ]))
                <li class="slide__category"><span class="category-name">Purchase</span></li>
                <li class="slide has-sub {{ request()->routeIs('admin.purchase-orders.*','admin.goods-receipts.*','admin.purchase-bills.*','admin.purchase-returns.*','admin.purchase-suggestions.*','admin.purchase-indents.*','admin.purchase-rfqs.*','admin.supplier-ratings.*') ? 'open' : '' }}">
                    <a href="javascript:void(0);" class="side-menu__item">
                        <i class="bx bx-cart side-menu__icon"></i>
                        <span class="side-menu__label">Purchase</span>
                        <i class="fe fe-chevron-right side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1" style="{{ request()->routeIs('admin.purchase-orders.*','admin.goods-receipts.*','admin.purchase-bills.*','admin.purchase-returns.*','admin.purchase-suggestions.*','admin.purchase-indents.*','admin.purchase-rfqs.*','admin.supplier-ratings.*') ? 'display:block' : '' }}">
                        @can('purchase_suggestion.view')<li class="slide"><a href="{{ route('admin.purchase-suggestions.index') }}" class="side-menu__item">Suggestions</a></li>@endcan
                        @can('purchase_indent.view')<li class="slide"><a href="{{ route('admin.purchase-indents.index') }}" class="side-menu__item">Indents</a></li>@endcan
                        @can('purchase_rfq.view')<li class="slide"><a href="{{ route('admin.purchase-rfqs.index') }}" class="side-menu__item">RFQs</a></li>@endcan
                        @can('purchase_order.view')<li class="slide"><a href="{{ route('admin.purchase-orders.index') }}" class="side-menu__item">Purchase Orders</a></li>@endcan
                        @can('goods_receipt.view')<li class="slide"><a href="{{ route('admin.goods-receipts.index') }}" class="side-menu__item">Goods Receipts</a></li>@endcan
                        @can('purchase_bill.view')<li class="slide"><a href="{{ route('admin.purchase-bills.index') }}" class="side-menu__item">Purchase Bills</a></li>@endcan
                        @can('purchase_return.view')<li class="slide"><a href="{{ route('admin.purchase-returns.index') }}" class="side-menu__item">Purchase Returns</a></li>@endcan
                        @can('supplier_rating.view')<li class="slide"><a href="{{ route('admin.supplier-ratings.index') }}" class="side-menu__item">Supplier Ratings</a></li>@endcan
                    </ul>
                </li>
                @endif
                @if (auth()->user()?->hasAnyPermission(['lead.view', 'opportunity.view', 'crm_report.view']))
                <li class="slide__category"><span class="category-name">CRM</span></li>
                <li class="slide has-sub {{ request()->routeIs('admin.leads.*','admin.opportunities.*','admin.crm-reports.*') ? 'open' : '' }}">
                    <a href="javascript:void(0);" class="side-menu__item">
                        <i class="bx bx-user-voice side-menu__icon"></i>
                        <span class="side-menu__label">CRM</span>
                        <i class="fe fe-chevron-right side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1" style="{{ request()->routeIs('admin.leads.*','admin.opportunities.*','admin.crm-reports.*') ? 'display:block' : '' }}">
                        @can('lead.view')<li class="slide"><a href="{{ route('admin.leads.index') }}" class="side-menu__item">Leads</a></li>@endcan
                        @can('opportunity.view')<li class="slide"><a href="{{ route('admin.opportunities.index') }}" class="side-menu__item">Opportunities</a></li>@endcan
                        @can('opportunity.view')<li class="slide"><a href="{{ route('admin.opportunities.pipeline') }}" class="side-menu__item">Pipeline Board</a></li>@endcan
                        @can('crm_report.view')<li class="slide"><a href="{{ route('admin.crm-reports.funnel') }}" class="side-menu__item">Funnel</a></li>@endcan
                        @can('crm_report.view')<li class="slide"><a href="{{ route('admin.crm-reports.overdue') }}" class="side-menu__item">Overdue Follow-ups</a></li>@endcan
                    </ul>
                </li>
                @endif
                @if (auth()->user()?->hasAnyPermission([
                    'sales_quotation.view', 'sales_order.view', 'sales_invoice.view', 'delivery_challan.view', 'sales_return.view', 'price_list.view',
                ]))
                <li class="slide__category"><span class="category-name">Sales</span></li>
                <li class="slide has-sub {{ request()->routeIs('admin.sales-quotations.*','admin.sales-orders.*','admin.sales-invoices.*','admin.delivery-challans.*','admin.sales-returns.*','admin.price-lists.*') ? 'open' : '' }}">
                    <a href="javascript:void(0);" class="side-menu__item">
                        <i class="bx bx-shopping-bag side-menu__icon"></i>
                        <span class="side-menu__label">Sales</span>
                        <i class="fe fe-chevron-right side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1" style="{{ request()->routeIs('admin.sales-quotations.*','admin.sales-orders.*','admin.sales-invoices.*','admin.delivery-challans.*','admin.sales-returns.*','admin.price-lists.*') ? 'display:block' : '' }}">
                        @can('sales_quotation.view')<li class="slide"><a href="{{ route('admin.sales-quotations.index') }}" class="side-menu__item">Quotations</a></li>@endcan
                        @can('price_list.view')<li class="slide"><a href="{{ route('admin.price-lists.index') }}" class="side-menu__item">Price Lists</a></li>@endcan
                        @can('sales_order.view')<li class="slide"><a href="{{ route('admin.sales-orders.index') }}" class="side-menu__item">Sales Orders</a></li>@endcan
                        @can('delivery_challan.view')<li class="slide"><a href="{{ route('admin.delivery-challans.index') }}" class="side-menu__item">Delivery Challans</a></li>@endcan
                        @can('sales_invoice.view')<li class="slide"><a href="{{ route('admin.sales-invoices.index') }}" class="side-menu__item">Invoices</a></li>@endcan
                        @can('sales_return.view')<li class="slide"><a href="{{ route('admin.sales-returns.index') }}" class="side-menu__item">Sales Returns</a></li>@endcan
                    </ul>
                </li>
                @endif
                @if (auth()->user()?->hasAnyPermission(['packing_unit.view', 'package.view', 'package.scan']))
                <li class="slide__category"><span class="category-name">Packing & Dispatch</span></li>
                <li class="slide has-sub {{ request()->routeIs('admin.packing-units.*','admin.packages.*') ? 'open' : '' }}">
                    <a href="javascript:void(0);" class="side-menu__item">
                        <i class="bx bx-box side-menu__icon"></i>
                        <span class="side-menu__label">Packing</span>
                        <i class="fe fe-chevron-right side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1" style="{{ request()->routeIs('admin.packing-units.*','admin.packages.*') ? 'display:block' : '' }}">
                        @can('packing_unit.view')<li class="slide"><a href="{{ route('admin.packing-units.index') }}" class="side-menu__item">Packing Units</a></li>@endcan
                        @can('package.view')<li class="slide"><a href="{{ route('admin.packages.pack') }}" class="side-menu__item">Pack & Label</a></li>@endcan
                        @can('package.view')<li class="slide"><a href="{{ route('admin.packages.index') }}" class="side-menu__item">Packages</a></li>@endcan
                        @can('package.scan')<li class="slide"><a href="{{ route('admin.packages.scan-form') }}" class="side-menu__item">Gate Scan</a></li>@endcan
                        @can('scan_exception.view')<li class="slide"><a href="{{ route('admin.scan-exceptions.index') }}" class="side-menu__item">Scan Exceptions</a></li>@endcan
                    </ul>
                </li>
                @endif
                @if (($industry?->feature('production') ?? true) && auth()->user()?->hasAnyPermission([
                    'production_plan.view', 'work_order.view', 'production_entry.view', 'shop_floor.view',
                ]))
                <li class="slide__category"><span class="category-name">Production</span></li>
                <li class="slide has-sub {{ request()->routeIs('admin.production-plans.*','admin.work-orders.*','admin.production-entries.*','admin.shop-floor.*') ? 'open' : '' }}">
                    <a href="javascript:void(0);" class="side-menu__item">
                        <i class="bx bx-cog side-menu__icon"></i>
                        <span class="side-menu__label">Production</span>
                        <i class="fe fe-chevron-right side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1" style="{{ request()->routeIs('admin.production-plans.*','admin.work-orders.*','admin.production-entries.*','admin.shop-floor.*') ? 'display:block' : '' }}">
                        @can('production_plan.view')<li class="slide"><a href="{{ route('admin.production-plans.index') }}" class="side-menu__item">Production Plans</a></li>@endcan
                        @can('work_order.view')<li class="slide"><a href="{{ route('admin.work-orders.index') }}" class="side-menu__item">Work Orders</a></li>@endcan
                        @can('production_entry.view')<li class="slide"><a href="{{ route('admin.production-entries.index') }}" class="side-menu__item">Production Entries</a></li>@endcan
                        @can('shop_floor.view')<li class="slide"><a href="{{ route('admin.shop-floor.operator') }}" class="side-menu__item">Operator Board</a></li>@endcan
                        @can('shop_floor.view')<li class="slide"><a href="{{ route('admin.shop-floor.capacity') }}" class="side-menu__item">Capacity Chart</a></li>@endcan
                    </ul>
                </li>
                @endif
                @if (($industry?->feature('quality') ?? true) && auth()->user()?->hasAnyPermission([
                    'qc_template.view', 'qc_inspection.view', 'qc_report.view',
                ]))
                <li class="slide__category"><span class="category-name">Quality</span></li>
                <li class="slide has-sub {{ request()->routeIs('admin.qc-templates.*','admin.qc-inspections.*','admin.qc-reports.*') ? 'open' : '' }}">
                    <a href="javascript:void(0);" class="side-menu__item">
                        <i class="bx bx-check-shield side-menu__icon"></i>
                        <span class="side-menu__label">Quality Control</span>
                        <i class="fe fe-chevron-right side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1" style="{{ request()->routeIs('admin.qc-templates.*','admin.qc-inspections.*','admin.qc-reports.*') ? 'display:block' : '' }}">
                        @can('qc_template.view')<li class="slide"><a href="{{ route('admin.qc-templates.index') }}" class="side-menu__item">QC Templates</a></li>@endcan
                        @can('qc_inspection.view')<li class="slide"><a href="{{ route('admin.qc-inspections.index') }}" class="side-menu__item">Inspections</a></li>@endcan
                        @can('qc_report.view')<li class="slide"><a href="{{ route('admin.qc-reports.pareto') }}" class="side-menu__item">Defect Pareto</a></li>@endcan
                    </ul>
                </li>
                @endif
                @if (($industry?->feature('maintenance') ?? true) && auth()->user()?->hasAnyPermission([
                    'work_centre.view', 'maintenance_order.view',
                ]))
                <li class="slide__category"><span class="category-name">Maintenance</span></li>
                <li class="slide has-sub {{ request()->routeIs('admin.work-centres.*','admin.maintenance-orders.*') ? 'open' : '' }}">
                    <a href="javascript:void(0);" class="side-menu__item">
                        <i class="bx bx-wrench side-menu__icon"></i>
                        <span class="side-menu__label">Maintenance</span>
                        <i class="fe fe-chevron-right side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1" style="{{ request()->routeIs('admin.work-centres.*','admin.maintenance-orders.*') ? 'display:block' : '' }}">
                        @can('work_centre.view')<li class="slide"><a href="{{ route('admin.work-centres.index') }}" class="side-menu__item">Assets</a></li>@endcan
                        @can('maintenance_order.view')<li class="slide"><a href="{{ route('admin.maintenance-orders.index') }}" class="side-menu__item">Maintenance Orders</a></li>@endcan
                        @can('work_centre.view')<li class="slide"><a href="{{ route('admin.work-centres.due') }}" class="side-menu__item">PM Due</a></li>@endcan
                        @can('work_centre.view')<li class="slide"><a href="{{ route('admin.work-centres.status-board') }}" class="side-menu__item">Status Board</a></li>@endcan
                    </ul>
                </li>
                @endif
                @if (auth()->user()?->hasAnyPermission([
                    'opening_stock.view', 'stock_adjustment.view', 'stock_transfer.view', 'stock_balance.view', 'stock_ledger.view', 'stock_take.view',
                ]))
                <li class="slide__category"><span class="category-name">Inventory</span></li>
                <li class="slide has-sub {{ request()->routeIs('admin.opening-stocks.*','admin.stock-adjustments.*','admin.stock-transfers.*','admin.stock-balances.*','admin.stock-ledger.*','admin.stock-takes.*') ? 'open' : '' }}">
                    <a href="javascript:void(0);" class="side-menu__item">
                        <i class="bx bx-package side-menu__icon"></i>
                        <span class="side-menu__label">Inventory</span>
                        <i class="fe fe-chevron-right side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1" style="{{ request()->routeIs('admin.opening-stocks.*','admin.stock-adjustments.*','admin.stock-transfers.*','admin.stock-balances.*','admin.stock-ledger.*','admin.stock-takes.*') ? 'display:block' : '' }}">
                        @can('opening_stock.view')<li class="slide"><a href="{{ route('admin.opening-stocks.index') }}" class="side-menu__item">Opening Stock</a></li>@endcan
                        @can('stock_take.view')<li class="slide"><a href="{{ route('admin.stock-takes.index') }}" class="side-menu__item">Stock Takes</a></li>@endcan
                        @can('stock_adjustment.view')<li class="slide"><a href="{{ route('admin.stock-adjustments.index') }}" class="side-menu__item">Adjustments</a></li>@endcan
                        @can('stock_transfer.view')<li class="slide"><a href="{{ route('admin.stock-transfers.index') }}" class="side-menu__item">Transfers</a></li>@endcan
                        @can('stock_balance.view')<li class="slide"><a href="{{ route('admin.stock-balances.index') }}" class="side-menu__item">Stock Balances</a></li>@endcan
                        @can('stock_ledger.view')<li class="slide"><a href="{{ route('admin.stock-ledger.index') }}" class="side-menu__item">Stock Ledger</a></li>@endcan
                    </ul>
                </li>
                @endif
                @if (auth()->user()?->hasAnyPermission([
                    'ledger_account.view', 'journal_voucher.view', 'finance_report.view', 'gst_report.view', 'period_lock.view', 'voucher_allocation.view', 'bank_reconciliation.view',
                ]))
                <li class="slide__category"><span class="category-name">Finance</span></li>
                <li class="slide has-sub {{ request()->routeIs('admin.ledger-accounts.*','admin.journal-vouchers.*','admin.finance-reports.*','admin.gst-reports.*','admin.gstr2b.*','admin.bank-reconciliation.*','admin.period-locks.*','admin.voucher-allocations.*') ? 'open' : '' }}">
                    <a href="javascript:void(0);" class="side-menu__item">
                        <i class="bx bx-book side-menu__icon"></i>
                        <span class="side-menu__label">Accounts & GST</span>
                        <i class="fe fe-chevron-right side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1" style="{{ request()->routeIs('admin.ledger-accounts.*','admin.journal-vouchers.*','admin.finance-reports.*','admin.gst-reports.*','admin.gstr2b.*','admin.bank-reconciliation.*','admin.period-locks.*','admin.voucher-allocations.*') ? 'display:block' : '' }}">
                        @can('ledger_account.view')<li class="slide"><a href="{{ route('admin.ledger-accounts.index') }}" class="side-menu__item">Chart of Accounts</a></li>@endcan
                        @can('journal_voucher.view')<li class="slide"><a href="{{ route('admin.journal-vouchers.index') }}" class="side-menu__item">Journal Vouchers</a></li>@endcan
                        @can('period_lock.view')<li class="slide"><a href="{{ route('admin.period-locks.index') }}" class="side-menu__item">Period Lock</a></li>@endcan
                        @can('bank_reconciliation.view')<li class="slide"><a href="{{ route('admin.bank-reconciliation.index') }}" class="side-menu__item">Bank Reconciliation</a></li>@endcan
                        @can('finance_report.view')<li class="slide"><a href="{{ route('admin.finance-reports.ageing') }}" class="side-menu__item">Receivable Ageing</a></li>@endcan
                        @can('finance_report.view')<li class="slide"><a href="{{ route('admin.finance-reports.ageing', ['type' => 'payable']) }}" class="side-menu__item">Payable Ageing</a></li>@endcan
                        @can('finance_report.view')<li class="slide"><a href="{{ route('admin.finance-reports.statement') }}" class="side-menu__item">Account Statement</a></li>@endcan
                        @can('finance_report.view')<li class="slide"><a href="{{ route('admin.finance-reports.trial-balance') }}" class="side-menu__item">Trial Balance</a></li>@endcan
                        @can('finance_report.view')<li class="slide"><a href="{{ route('admin.finance-reports.profit-and-loss') }}" class="side-menu__item">Profit &amp; Loss</a></li>@endcan
                        @can('finance_report.view')<li class="slide"><a href="{{ route('admin.finance-reports.balance-sheet') }}" class="side-menu__item">Balance Sheet</a></li>@endcan
                        @can('gst_report.view')<li class="slide"><a href="{{ route('admin.gst-reports.index') }}" class="side-menu__item">GST Worksheets</a></li>@endcan
                        @can('gst_report.view')<li class="slide"><a href="{{ route('admin.gstr2b.index') }}" class="side-menu__item">GSTR-2B Import</a></li>@endcan
                    </ul>
                </li>
                @endif
                @if (auth()->user()?->hasAnyPermission([
                    'employee.view', 'shift.view', 'attendance.view', 'salary_run.view', 'holiday.view',
                ]))
                <li class="slide__category"><span class="category-name">Human Resources</span></li>
                <li class="slide has-sub {{ request()->routeIs('admin.employees.*','admin.shifts.*','admin.attendance.*','admin.salary-runs.*','admin.holidays.*') ? 'open' : '' }}">
                    <a href="javascript:void(0);" class="side-menu__item">
                        <i class="bx bx-id-card side-menu__icon"></i>
                        <span class="side-menu__label">People & Payroll</span>
                        <i class="fe fe-chevron-right side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1" style="{{ request()->routeIs('admin.employees.*','admin.shifts.*','admin.attendance.*','admin.salary-runs.*','admin.holidays.*') ? 'display:block' : '' }}">
                        @can('employee.view')<li class="slide"><a href="{{ route('admin.employees.index') }}" class="side-menu__item">Employees</a></li>@endcan
                        @can('shift.view')<li class="slide"><a href="{{ route('admin.shifts.index') }}" class="side-menu__item">Shifts</a></li>@endcan
                        @can('attendance.view')<li class="slide"><a href="{{ route('admin.attendance.index') }}" class="side-menu__item">Daily Attendance</a></li>@endcan
                        @can('holiday.view')<li class="slide"><a href="{{ route('admin.holidays.index') }}" class="side-menu__item">Holidays & Leave</a></li>@endcan
                        @can('salary_run.view')<li class="slide"><a href="{{ route('admin.salary-runs.index') }}" class="side-menu__item">Salary Runs</a></li>@endcan
                    </ul>
                </li>
                @endif
                @can('report.view')
                <li class="slide__category"><span class="category-name">Reports</span></li>
                <li class="slide has-sub {{ request()->routeIs('admin.reports.*') ? 'open' : '' }}">
                    <a href="javascript:void(0);" class="side-menu__item">
                        <i class="bx bx-bar-chart-square side-menu__icon"></i>
                        <span class="side-menu__label">Registers</span>
                        <i class="fe fe-chevron-right side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1" style="{{ request()->routeIs('admin.reports.*') ? 'display:block' : '' }}">
                        <li class="slide"><a href="{{ route('admin.reports.show', 'sales') }}" class="side-menu__item">Sales Register</a></li>
                        <li class="slide"><a href="{{ route('admin.reports.show', 'purchase') }}" class="side-menu__item">Purchase Register</a></li>
                        <li class="slide"><a href="{{ route('admin.reports.show', 'pending-sales-orders') }}" class="side-menu__item">Pending Sales Orders</a></li>
                        <li class="slide"><a href="{{ route('admin.reports.show', 'pending-purchase-orders') }}" class="side-menu__item">Pending Purchase Orders</a></li>
                        <li class="slide"><a href="{{ route('admin.reports.show', 'stock') }}" class="side-menu__item">Stock Movement</a></li>
                        <li class="slide"><a href="{{ route('admin.reports.show', 'stock-valuation') }}" class="side-menu__item">Stock Valuation</a></li>
                        <li class="slide"><a href="{{ route('admin.reports.show', 'slow-moving') }}" class="side-menu__item">Slow / Dead Stock</a></li>
                        <li class="slide"><a href="{{ route('admin.reports.show', 'day-book') }}" class="side-menu__item">Day Book</a></li>
                        <li class="slide"><a href="{{ route('admin.reports.show', 'production') }}" class="side-menu__item">Production Register</a></li>
                        <li class="slide"><a href="{{ route('admin.reports.show', 'rejection') }}" class="side-menu__item">Rejection Register</a></li>
                        <li class="slide"><a href="{{ route('admin.reports.show', 'sales-executive') }}" class="side-menu__item">Sales Executive</a></li>
                        <li class="slide"><a href="{{ route('admin.reports.show', 'purchase-vs-indent') }}" class="side-menu__item">Purchase vs Indent</a></li>
                        <li class="slide"><a href="{{ route('admin.reports.show', 'grn-pending-inspection') }}" class="side-menu__item">GRN Pending Inspection</a></li>
                        @can('scheduled_report.view')<li class="slide"><a href="{{ route('admin.scheduled-reports.index') }}" class="side-menu__item">Scheduled Reports</a></li>@endcan
                    </ul>
                </li>
                @endcan
                @if (auth()->user()?->hasAnyPermission([
                    'setting.view', 'financial_year.view', 'document_series.view', 'notification_rule.view', 'activity_log.view', 'system.view', 'custom_field.view', 'approval_rule.view', 'print_template.view', 'terms_block.view', 'ui_label.view', 'industry_profile.view', 'backup.view', 'recycle_bin.view',
                ]))
                <li class="slide__category"><span class="category-name">Settings</span></li>
                <li class="slide has-sub {{ request()->routeIs('admin.settings.*','admin.dashboard-widgets.*','admin.financial-years.*','admin.document-series.*','admin.notification-rules.*','admin.activity-logs.*','admin.system.*','admin.custom-fields.*','admin.approval-rules.*','admin.print-templates.*','admin.terms-blocks.*','admin.ui-labels.*','admin.industry-profiles.*','admin.backups.*','admin.recycle-bin.*') ? 'open' : '' }}">
                    <a href="javascript:void(0);" class="side-menu__item">
                        <i class="bx bx-cog side-menu__icon"></i>
                        <span class="side-menu__label">Settings & Utilities</span>
                        <i class="fe fe-chevron-right side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1" style="{{ request()->routeIs('admin.settings.*','admin.dashboard-widgets.*','admin.financial-years.*','admin.document-series.*','admin.notification-rules.*','admin.activity-logs.*','admin.system.*','admin.custom-fields.*','admin.approval-rules.*','admin.print-templates.*','admin.terms-blocks.*','admin.ui-labels.*','admin.industry-profiles.*','admin.backups.*','admin.recycle-bin.*') ? 'display:block' : '' }}">
                        @can('setting.view')<li class="slide"><a href="{{ route('admin.settings.edit') }}" class="side-menu__item">General Settings</a></li>@endcan
                        @can('setting.update')<li class="slide"><a href="{{ route('admin.dashboard-widgets.index') }}" class="side-menu__item">Dashboard Widgets</a></li>@endcan
                        @can('financial_year.view')<li class="slide"><a href="{{ route('admin.financial-years.index') }}" class="side-menu__item">Financial Years</a></li>@endcan
                        @can('document_series.view')<li class="slide"><a href="{{ route('admin.document-series.index') }}" class="side-menu__item">Number Series</a></li>@endcan
                        @can('industry_profile.view')<li class="slide"><a href="{{ route('admin.industry-profiles.index') }}" class="side-menu__item">Industry Profiles</a></li>@endcan
                        @can('custom_field.view')<li class="slide"><a href="{{ route('admin.custom-fields.index') }}" class="side-menu__item">Custom Fields</a></li>@endcan
                        @can('approval_rule.view')<li class="slide"><a href="{{ route('admin.approval-rules.index') }}" class="side-menu__item">Approval Rules</a></li>@endcan
                        @can('print_template.view')<li class="slide"><a href="{{ route('admin.print-templates.index') }}" class="side-menu__item">Print Templates</a></li>@endcan
                        @can('terms_block.view')<li class="slide"><a href="{{ route('admin.terms-blocks.index') }}" class="side-menu__item">Terms Library</a></li>@endcan
                        @can('ui_label.view')<li class="slide"><a href="{{ route('admin.ui-labels.index') }}" class="side-menu__item">UI Labels</a></li>@endcan
                        @can('notification_rule.view')<li class="slide"><a href="{{ route('admin.notification-rules.index') }}" class="side-menu__item">Notification Rules</a></li>@endcan
                        @can('activity_log.view')<li class="slide"><a href="{{ route('admin.activity-logs.index') }}" class="side-menu__item">Activity Log</a></li>@endcan
                        @can('recycle_bin.view')<li class="slide"><a href="{{ route('admin.recycle-bin.index') }}" class="side-menu__item">Recycle Bin</a></li>@endcan
                        @can('backup.view')<li class="slide"><a href="{{ route('admin.backups.index') }}" class="side-menu__item">Backups</a></li>@endcan
                        @can('system.view')<li class="slide"><a href="{{ route('admin.system.health') }}" class="side-menu__item">System Health</a></li>@endcan
                    </ul>
                </li>
                @endif
                @if (auth()->user()?->hasAnyPermission(['user.view', 'role.view']))
                <li class="slide__category"><span class="category-name">Access Control</span></li>
                <li class="slide has-sub {{ request()->routeIs('admin.users.*','admin.roles.*') ? 'open' : '' }}">
                    <a href="javascript:void(0);" class="side-menu__item">
                        <i class="bx bx-shield side-menu__icon"></i>
                        <span class="side-menu__label">Users & Roles</span>
                        <i class="fe fe-chevron-right side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1" style="{{ request()->routeIs('admin.users.*','admin.roles.*') ? 'display:block' : '' }}">
                        @can('user.view')<li class="slide"><a href="{{ route('admin.users.index') }}" class="side-menu__item">Users</a></li>@endcan
                        @can('role.view')<li class="slide"><a href="{{ route('admin.roles.index') }}" class="side-menu__item">Roles & Permissions</a></li>@endcan
                    </ul>
                </li>
                @endif
            </ul>
        </nav>
    </div>
</aside>
