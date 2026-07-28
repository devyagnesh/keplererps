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
            @php
                $moduleHelp = static function (string $key): ?string {
                    return \Illuminate\Support\Facades\Lang::has('modules.'.$key) ? __('modules.'.$key) : null;
                };
            @endphp
            <ul class="main-menu">
                <li class="slide__category"><span class="category-name">Main</span></li>
                <li class="slide">
                    <a href="{{ route('admin.dashboard') }}" class="side-menu__item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" title="{{ $moduleHelp('dashboard') }}">
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
                        @can('company.view')<li class="slide"><a href="{{ route('admin.company.edit') }}" class="side-menu__item" title="{{ $moduleHelp('company') }}">Company Setup</a></li>@endcan
                        @can('branch.view')<li class="slide"><a href="{{ route('admin.branches.index') }}" class="side-menu__item" title="{{ $moduleHelp('branches') }}">Branches</a></li>@endcan
                        @can('warehouse.view')<li class="slide"><a href="{{ route('admin.warehouses.index') }}" class="side-menu__item" title="{{ $moduleHelp('warehouses') }}">Warehouses</a></li>@endcan
                        @can('party.view')
                            <li class="slide"><a href="{{ route('admin.parties.index') }}" class="side-menu__item" title="{{ $moduleHelp('parties') }}">Customers & Suppliers</a></li>
                            <li class="slide"><a href="{{ route('admin.parties.import.index') }}" class="side-menu__item" title="{{ $moduleHelp('parties.import') }}">Import Parties</a></li>
                        @endcan
                        @can('tax_rate.view')<li class="slide"><a href="{{ route('admin.tax-rates.index') }}" class="side-menu__item" title="{{ $moduleHelp('tax-rates') }}">Tax Rates</a></li>@endcan
                        @can('uom.view')<li class="slide"><a href="{{ route('admin.uoms.index') }}" class="side-menu__item" title="{{ $moduleHelp('uoms') }}">UOM</a></li>@endcan
                        @can('category.view')<li class="slide"><a href="{{ route('admin.categories.index') }}" class="side-menu__item" title="{{ $moduleHelp('categories') }}">Categories</a></li>@endcan
                        @can('transporter.view')<li class="slide"><a href="{{ route('admin.transporters.index') }}" class="side-menu__item" title="{{ $moduleHelp('transporters') }}">Transporters</a></li>@endcan
                        @can('hsn_code.view')<li class="slide"><a href="{{ route('admin.hsn-codes.index') }}" class="side-menu__item" title="{{ $moduleHelp('hsn-codes') }}">HSN / SAC</a></li>@endcan
                        @can('item.view')<li class="slide"><a href="{{ route('admin.items.index') }}" class="side-menu__item" title="{{ $moduleHelp('items') }}">Items</a></li>@endcan
                        @can('bom.view')<li class="slide"><a href="{{ route('admin.boms.index') }}" class="side-menu__item" title="{{ $moduleHelp('boms') }}">Bill of Materials</a></li>@endcan
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
                        @can('purchase_suggestion.view')<li class="slide"><a href="{{ route('admin.purchase-suggestions.index') }}" class="side-menu__item" title="{{ $moduleHelp('purchase-suggestions') }}">Suggestions</a></li>@endcan
                        @can('purchase_indent.view')<li class="slide"><a href="{{ route('admin.purchase-indents.index') }}" class="side-menu__item" title="{{ $moduleHelp('purchase-indents') }}">Indents</a></li>@endcan
                        @can('purchase_rfq.view')<li class="slide"><a href="{{ route('admin.purchase-rfqs.index') }}" class="side-menu__item" title="{{ $moduleHelp('purchase-rfqs') }}">RFQs</a></li>@endcan
                        @can('purchase_order.view')<li class="slide"><a href="{{ route('admin.purchase-orders.index') }}" class="side-menu__item" title="{{ $moduleHelp('purchase-orders') }}">Purchase Orders</a></li>@endcan
                        @can('goods_receipt.view')<li class="slide"><a href="{{ route('admin.goods-receipts.index') }}" class="side-menu__item" title="{{ $moduleHelp('goods-receipts') }}">Goods Receipts</a></li>@endcan
                        @can('purchase_bill.view')<li class="slide"><a href="{{ route('admin.purchase-bills.index') }}" class="side-menu__item" title="{{ $moduleHelp('purchase-bills') }}">Purchase Bills</a></li>@endcan
                        @can('purchase_return.view')<li class="slide"><a href="{{ route('admin.purchase-returns.index') }}" class="side-menu__item" title="{{ $moduleHelp('purchase-returns') }}">Purchase Returns</a></li>@endcan
                        @can('supplier_rating.view')<li class="slide"><a href="{{ route('admin.supplier-ratings.index') }}" class="side-menu__item" title="{{ $moduleHelp('supplier-ratings') }}">Supplier Ratings</a></li>@endcan
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
                        @can('lead.view')<li class="slide"><a href="{{ route('admin.leads.index') }}" class="side-menu__item" title="{{ $moduleHelp('leads') }}">Leads</a></li>@endcan
                        @can('opportunity.view')<li class="slide"><a href="{{ route('admin.opportunities.index') }}" class="side-menu__item" title="{{ $moduleHelp('opportunities') }}">Opportunities</a></li>@endcan
                        @can('opportunity.view')<li class="slide"><a href="{{ route('admin.opportunities.pipeline') }}" class="side-menu__item" title="{{ $moduleHelp('opportunities.pipeline') }}">Pipeline Board</a></li>@endcan
                        @can('crm_report.view')<li class="slide"><a href="{{ route('admin.crm-reports.funnel') }}" class="side-menu__item" title="{{ $moduleHelp('crm-reports.funnel') }}">Funnel</a></li>@endcan
                        @can('crm_report.view')<li class="slide"><a href="{{ route('admin.crm-reports.overdue') }}" class="side-menu__item" title="{{ $moduleHelp('crm-reports.overdue') }}">Overdue Follow-ups</a></li>@endcan
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
                        @can('sales_quotation.view')<li class="slide"><a href="{{ route('admin.sales-quotations.index') }}" class="side-menu__item" title="{{ $moduleHelp('sales-quotations') }}">Quotations</a></li>@endcan
                        @can('price_list.view')<li class="slide"><a href="{{ route('admin.price-lists.index') }}" class="side-menu__item" title="{{ $moduleHelp('price-lists') }}">Price Lists</a></li>@endcan
                        @can('sales_order.view')<li class="slide"><a href="{{ route('admin.sales-orders.index') }}" class="side-menu__item" title="{{ $moduleHelp('sales-orders') }}">Sales Orders</a></li>@endcan
                        @can('delivery_challan.view')<li class="slide"><a href="{{ route('admin.delivery-challans.index') }}" class="side-menu__item" title="{{ $moduleHelp('delivery-challans') }}">Delivery Challans</a></li>@endcan
                        @can('sales_invoice.view')<li class="slide"><a href="{{ route('admin.sales-invoices.index') }}" class="side-menu__item" title="{{ $moduleHelp('sales-invoices') }}">Invoices</a></li>@endcan
                        @can('sales_return.view')<li class="slide"><a href="{{ route('admin.sales-returns.index') }}" class="side-menu__item" title="{{ $moduleHelp('sales-returns') }}">Sales Returns</a></li>@endcan
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
                        @can('packing_unit.view')<li class="slide"><a href="{{ route('admin.packing-units.index') }}" class="side-menu__item" title="{{ $moduleHelp('packing-units') }}">Packing Units</a></li>@endcan
                        @can('package.view')<li class="slide"><a href="{{ route('admin.packages.pack') }}" class="side-menu__item" title="{{ $moduleHelp('packages.pack') }}">Pack & Label</a></li>@endcan
                        @can('package.view')<li class="slide"><a href="{{ route('admin.packages.index') }}" class="side-menu__item" title="{{ $moduleHelp('packages') }}">Packages</a></li>@endcan
                        @can('package.scan')<li class="slide"><a href="{{ route('admin.packages.scan-form') }}" class="side-menu__item" title="{{ $moduleHelp('packages.scan-form') }}">Gate Scan</a></li>@endcan
                        @can('scan_exception.view')<li class="slide"><a href="{{ route('admin.scan-exceptions.index') }}" class="side-menu__item" title="{{ $moduleHelp('scan-exceptions') }}">Scan Exceptions</a></li>@endcan
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
                        @can('production_plan.view')<li class="slide"><a href="{{ route('admin.production-plans.index') }}" class="side-menu__item" title="{{ $moduleHelp('production-plans') }}">Production Plans</a></li>@endcan
                        @can('work_order.view')<li class="slide"><a href="{{ route('admin.work-orders.index') }}" class="side-menu__item" title="{{ $moduleHelp('work-orders') }}">Work Orders</a></li>@endcan
                        @can('production_entry.view')<li class="slide"><a href="{{ route('admin.production-entries.index') }}" class="side-menu__item" title="{{ $moduleHelp('production-entries') }}">Production Entries</a></li>@endcan
                        @can('shop_floor.view')<li class="slide"><a href="{{ route('admin.shop-floor.operator') }}" class="side-menu__item" title="{{ $moduleHelp('shop-floor.operator') }}">Operator Board</a></li>@endcan
                        @can('shop_floor.view')<li class="slide"><a href="{{ route('admin.shop-floor.capacity') }}" class="side-menu__item" title="{{ $moduleHelp('shop-floor.capacity') }}">Capacity Chart</a></li>@endcan
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
                        @can('qc_template.view')<li class="slide"><a href="{{ route('admin.qc-templates.index') }}" class="side-menu__item" title="{{ $moduleHelp('qc-templates') }}">QC Templates</a></li>@endcan
                        @can('qc_inspection.view')<li class="slide"><a href="{{ route('admin.qc-inspections.index') }}" class="side-menu__item" title="{{ $moduleHelp('qc-inspections') }}">Inspections</a></li>@endcan
                        @can('qc_report.view')<li class="slide"><a href="{{ route('admin.qc-reports.pareto') }}" class="side-menu__item" title="{{ $moduleHelp('qc-reports.pareto') }}">Defect Pareto</a></li>@endcan
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
                        @can('work_centre.view')<li class="slide"><a href="{{ route('admin.work-centres.index') }}" class="side-menu__item" title="{{ $moduleHelp('work-centres') }}">Assets</a></li>@endcan
                        @can('maintenance_order.view')<li class="slide"><a href="{{ route('admin.maintenance-orders.index') }}" class="side-menu__item" title="{{ $moduleHelp('maintenance-orders') }}">Maintenance Orders</a></li>@endcan
                        @can('work_centre.view')<li class="slide"><a href="{{ route('admin.work-centres.due') }}" class="side-menu__item" title="{{ $moduleHelp('work-centres.due') }}">PM Due</a></li>@endcan
                        @can('work_centre.view')<li class="slide"><a href="{{ route('admin.work-centres.status-board') }}" class="side-menu__item" title="{{ $moduleHelp('work-centres.status-board') }}">Status Board</a></li>@endcan
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
                        @can('opening_stock.view')<li class="slide"><a href="{{ route('admin.opening-stocks.index') }}" class="side-menu__item" title="{{ $moduleHelp('opening-stocks') }}">Opening Stock</a></li>@endcan
                        @can('stock_take.view')<li class="slide"><a href="{{ route('admin.stock-takes.index') }}" class="side-menu__item" title="{{ $moduleHelp('stock-takes') }}">Stock Takes</a></li>@endcan
                        @can('stock_adjustment.view')<li class="slide"><a href="{{ route('admin.stock-adjustments.index') }}" class="side-menu__item" title="{{ $moduleHelp('stock-adjustments') }}">Adjustments</a></li>@endcan
                        @can('stock_transfer.view')<li class="slide"><a href="{{ route('admin.stock-transfers.index') }}" class="side-menu__item" title="{{ $moduleHelp('stock-transfers') }}">Transfers</a></li>@endcan
                        @can('stock_balance.view')<li class="slide"><a href="{{ route('admin.stock-balances.index') }}" class="side-menu__item" title="{{ $moduleHelp('stock-balances') }}">Stock Balances</a></li>@endcan
                        @can('stock_ledger.view')<li class="slide"><a href="{{ route('admin.stock-ledger.index') }}" class="side-menu__item" title="{{ $moduleHelp('stock-ledger') }}">Stock Ledger</a></li>@endcan
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
                        @can('ledger_account.view')<li class="slide"><a href="{{ route('admin.ledger-accounts.index') }}" class="side-menu__item" title="{{ $moduleHelp('ledger-accounts') }}">Chart of Accounts</a></li>@endcan
                        @can('journal_voucher.view')<li class="slide"><a href="{{ route('admin.journal-vouchers.index') }}" class="side-menu__item" title="{{ $moduleHelp('journal-vouchers') }}">Journal Vouchers</a></li>@endcan
                        @can('period_lock.view')<li class="slide"><a href="{{ route('admin.period-locks.index') }}" class="side-menu__item" title="{{ $moduleHelp('period-locks') }}">Period Lock</a></li>@endcan
                        @can('bank_reconciliation.view')<li class="slide"><a href="{{ route('admin.bank-reconciliation.index') }}" class="side-menu__item" title="{{ $moduleHelp('bank-reconciliation') }}">Bank Reconciliation</a></li>@endcan
                        @can('finance_report.view')<li class="slide"><a href="{{ route('admin.finance-reports.ageing') }}" class="side-menu__item" title="{{ $moduleHelp('finance-reports.ageing') }}">Receivable Ageing</a></li>@endcan
                        @can('finance_report.view')<li class="slide"><a href="{{ route('admin.finance-reports.ageing', ['type' => 'payable']) }}" class="side-menu__item" title="{{ $moduleHelp('finance-reports.ageing') }}">Payable Ageing</a></li>@endcan
                        @can('finance_report.view')<li class="slide"><a href="{{ route('admin.finance-reports.statement') }}" class="side-menu__item" title="{{ $moduleHelp('finance-reports.statement') }}">Account Statement</a></li>@endcan
                        @can('finance_report.view')<li class="slide"><a href="{{ route('admin.finance-reports.trial-balance') }}" class="side-menu__item" title="{{ $moduleHelp('finance-reports.trial-balance') }}">Trial Balance</a></li>@endcan
                        @can('finance_report.view')<li class="slide"><a href="{{ route('admin.finance-reports.profit-and-loss') }}" class="side-menu__item" title="{{ $moduleHelp('finance-reports.profit-and-loss') }}">Profit &amp; Loss</a></li>@endcan
                        @can('finance_report.view')<li class="slide"><a href="{{ route('admin.finance-reports.balance-sheet') }}" class="side-menu__item" title="{{ $moduleHelp('finance-reports.balance-sheet') }}">Balance Sheet</a></li>@endcan
                        @can('gst_report.view')<li class="slide"><a href="{{ route('admin.gst-reports.index') }}" class="side-menu__item" title="{{ $moduleHelp('gst-reports') }}">GST Worksheets</a></li>@endcan
                        @can('gst_report.view')<li class="slide"><a href="{{ route('admin.gstr2b.index') }}" class="side-menu__item" title="{{ $moduleHelp('gstr2b') }}">GSTR-2B Import</a></li>@endcan
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
                        @can('employee.view')<li class="slide"><a href="{{ route('admin.employees.index') }}" class="side-menu__item" title="{{ $moduleHelp('employees') }}">Employees</a></li>@endcan
                        @can('shift.view')<li class="slide"><a href="{{ route('admin.shifts.index') }}" class="side-menu__item" title="{{ $moduleHelp('shifts') }}">Shifts</a></li>@endcan
                        @can('attendance.view')<li class="slide"><a href="{{ route('admin.attendance.index') }}" class="side-menu__item" title="{{ $moduleHelp('attendance') }}">Daily Attendance</a></li>@endcan
                        @can('holiday.view')<li class="slide"><a href="{{ route('admin.holidays.index') }}" class="side-menu__item" title="{{ $moduleHelp('holidays') }}">Holidays & Leave</a></li>@endcan
                        @can('salary_run.view')<li class="slide"><a href="{{ route('admin.salary-runs.index') }}" class="side-menu__item" title="{{ $moduleHelp('salary-runs') }}">Salary Runs</a></li>@endcan
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
                        <li class="slide"><a href="{{ route('admin.reports.show', 'sales') }}" class="side-menu__item" title="{{ $moduleHelp('reports.sales') }}">Sales Register</a></li>
                        <li class="slide"><a href="{{ route('admin.reports.show', 'purchase') }}" class="side-menu__item" title="{{ $moduleHelp('reports.purchase') }}">Purchase Register</a></li>
                        <li class="slide"><a href="{{ route('admin.reports.show', 'pending-sales-orders') }}" class="side-menu__item" title="{{ $moduleHelp('reports.pending-sales-orders') }}">Pending Sales Orders</a></li>
                        <li class="slide"><a href="{{ route('admin.reports.show', 'pending-purchase-orders') }}" class="side-menu__item" title="{{ $moduleHelp('reports.pending-purchase-orders') }}">Pending Purchase Orders</a></li>
                        <li class="slide"><a href="{{ route('admin.reports.show', 'stock') }}" class="side-menu__item" title="{{ $moduleHelp('reports.stock') }}">Stock Movement</a></li>
                        <li class="slide"><a href="{{ route('admin.reports.show', 'stock-valuation') }}" class="side-menu__item" title="{{ $moduleHelp('reports.stock-valuation') }}">Stock Valuation</a></li>
                        <li class="slide"><a href="{{ route('admin.reports.show', 'slow-moving') }}" class="side-menu__item" title="{{ $moduleHelp('reports.slow-moving') }}">Slow / Dead Stock</a></li>
                        <li class="slide"><a href="{{ route('admin.reports.show', 'day-book') }}" class="side-menu__item" title="{{ $moduleHelp('reports.day-book') }}">Day Book</a></li>
                        <li class="slide"><a href="{{ route('admin.reports.show', 'production') }}" class="side-menu__item" title="{{ $moduleHelp('reports.production') }}">Production Register</a></li>
                        <li class="slide"><a href="{{ route('admin.reports.show', 'rejection') }}" class="side-menu__item" title="{{ $moduleHelp('reports.rejection') }}">Rejection Register</a></li>
                        <li class="slide"><a href="{{ route('admin.reports.show', 'sales-executive') }}" class="side-menu__item" title="{{ $moduleHelp('reports.sales-executive') }}">Sales Executive</a></li>
                        <li class="slide"><a href="{{ route('admin.reports.show', 'purchase-vs-indent') }}" class="side-menu__item" title="{{ $moduleHelp('reports.purchase-vs-indent') }}">Purchase vs Indent</a></li>
                        <li class="slide"><a href="{{ route('admin.reports.show', 'grn-pending-inspection') }}" class="side-menu__item" title="{{ $moduleHelp('reports.grn-pending-inspection') }}">GRN Pending Inspection</a></li>
                        @can('scheduled_report.view')<li class="slide"><a href="{{ route('admin.scheduled-reports.index') }}" class="side-menu__item" title="{{ $moduleHelp('scheduled-reports') }}">Scheduled Reports</a></li>@endcan
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
                        @can('setting.view')<li class="slide"><a href="{{ route('admin.settings.edit') }}" class="side-menu__item" title="{{ $moduleHelp('settings') }}">General Settings</a></li>@endcan
                        @can('setting.update')<li class="slide"><a href="{{ route('admin.dashboard-widgets.index') }}" class="side-menu__item" title="{{ $moduleHelp('dashboard-widgets') }}">Dashboard Widgets</a></li>@endcan
                        @can('financial_year.view')<li class="slide"><a href="{{ route('admin.financial-years.index') }}" class="side-menu__item" title="{{ $moduleHelp('financial-years') }}">Financial Years</a></li>@endcan
                        @can('document_series.view')<li class="slide"><a href="{{ route('admin.document-series.index') }}" class="side-menu__item" title="{{ $moduleHelp('document-series') }}">Number Series</a></li>@endcan
                        @can('industry_profile.view')<li class="slide"><a href="{{ route('admin.industry-profiles.index') }}" class="side-menu__item" title="{{ $moduleHelp('industry-profiles') }}">Industry Profiles</a></li>@endcan
                        @can('custom_field.view')<li class="slide"><a href="{{ route('admin.custom-fields.index') }}" class="side-menu__item" title="{{ $moduleHelp('custom-fields') }}">Custom Fields</a></li>@endcan
                        @can('approval_rule.view')<li class="slide"><a href="{{ route('admin.approval-rules.index') }}" class="side-menu__item" title="{{ $moduleHelp('approval-rules') }}">Approval Rules</a></li>@endcan
                        @can('print_template.view')<li class="slide"><a href="{{ route('admin.print-templates.index') }}" class="side-menu__item" title="{{ $moduleHelp('print-templates') }}">Print Templates</a></li>@endcan
                        @can('terms_block.view')<li class="slide"><a href="{{ route('admin.terms-blocks.index') }}" class="side-menu__item" title="{{ $moduleHelp('terms-blocks') }}">Terms Library</a></li>@endcan
                        @can('ui_label.view')<li class="slide"><a href="{{ route('admin.ui-labels.index') }}" class="side-menu__item" title="{{ $moduleHelp('ui-labels') }}">UI Labels</a></li>@endcan
                        @can('notification_rule.view')<li class="slide"><a href="{{ route('admin.notification-rules.index') }}" class="side-menu__item" title="{{ $moduleHelp('notification-rules') }}">Notification Rules</a></li>@endcan
                        @can('activity_log.view')<li class="slide"><a href="{{ route('admin.activity-logs.index') }}" class="side-menu__item" title="{{ $moduleHelp('activity-logs') }}">Activity Log</a></li>@endcan
                        @can('recycle_bin.view')<li class="slide"><a href="{{ route('admin.recycle-bin.index') }}" class="side-menu__item" title="{{ $moduleHelp('recycle-bin') }}">Recycle Bin</a></li>@endcan
                        @can('backup.view')<li class="slide"><a href="{{ route('admin.backups.index') }}" class="side-menu__item" title="{{ $moduleHelp('backups') }}">Backups</a></li>@endcan
                        @can('system.view')<li class="slide"><a href="{{ route('admin.system.health') }}" class="side-menu__item" title="{{ $moduleHelp('system.health') }}">System Health</a></li>@endcan
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
                        @can('user.view')<li class="slide"><a href="{{ route('admin.users.index') }}" class="side-menu__item" title="{{ $moduleHelp('users') }}">Users</a></li>@endcan
                        @can('role.view')<li class="slide"><a href="{{ route('admin.roles.index') }}" class="side-menu__item" title="{{ $moduleHelp('roles') }}">Roles & Permissions</a></li>@endcan
                    </ul>
                </li>
                @endif
            </ul>
        </nav>
    </div>
</aside>
