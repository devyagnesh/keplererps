@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <p class="fw-semibold fs-18 mb-0">{{ $company?->trade_name ?? $company?->legal_name ?? 'Kepler ERP' }}</p>
        <span class="fs-semibold text-muted">Welcome, {{ auth()->user()->name }} · {{ now()->format('d M Y') }}</span>
    </div>
    @can('report.view')
    <a href="{{ route('admin.reports.show', 'sales') }}" class="btn btn-primary-light btn-sm">Open Registers</a>
    @endcan
</div>

<div class="row">
    @include('admin.dashboard.partials.metric', [
        'label' => 'Open Sales Orders',
        'value' => number_format($widgets['sales']['open_orders']),
        'caption' => '₹ '.number_format($widgets['sales']['open_order_value'], 2).' pending delivery',
        'icon' => 'bx-cart',
        'tone' => 'primary',
        'link' => Route::has('admin.sales-orders.index') ? route('admin.sales-orders.index') : null,
    ])
    @include('admin.dashboard.partials.metric', [
        'label' => 'Overdue Deliveries',
        'value' => number_format($widgets['sales']['overdue_deliveries']),
        'caption' => 'Confirmed orders past their promise date',
        'icon' => 'bx-time-five',
        'tone' => 'danger',
        'link' => Route::has('admin.sales-orders.index') ? route('admin.sales-orders.index') : null,
    ])
    @include('admin.dashboard.partials.metric', [
        'label' => 'Invoiced This Month',
        'value' => '₹ '.number_format($widgets['sales']['invoiced_this_month'], 2),
        'caption' => 'Confirmed sales invoices',
        'icon' => 'bx-receipt',
        'tone' => 'success',
        'link' => Route::has('admin.reports.show') ? route('admin.reports.show', 'sales') : null,
    ])
    @include('admin.dashboard.partials.metric', [
        'label' => 'Open Purchase Orders',
        'value' => number_format($widgets['purchase']['open_orders']),
        'caption' => '₹ '.number_format($widgets['purchase']['open_order_value'], 2).' on order',
        'icon' => 'bx-package',
        'tone' => 'info',
        'link' => Route::has('admin.purchase-orders.index') ? route('admin.purchase-orders.index') : null,
    ])
    @if(!empty($widgets['approvals']))
    @include('admin.dashboard.partials.metric', [
        'label' => 'Pending Approvals',
        'value' => number_format(($widgets['approvals']['purchase_orders'] ?? 0) + ($widgets['approvals']['sales_orders'] ?? 0)),
        'caption' => 'PO '.$widgets['approvals']['purchase_orders'].' · SO '.$widgets['approvals']['sales_orders'],
        'icon' => 'bx-check-circle',
        'tone' => 'warning',
        'link' => null,
    ])
    @endif
    @if(!empty($widgets['crm']))
    @include('admin.dashboard.partials.metric', [
        'label' => 'Overdue Follow-ups',
        'value' => number_format($widgets['crm']['overdue_follow_ups']),
        'caption' => 'CRM next-action past due',
        'icon' => 'bx-phone-call',
        'tone' => 'secondary',
        'link' => Route::has('admin.crm-reports.overdue') ? route('admin.crm-reports.overdue') : null,
    ])
    @endif
</div>

<div class="row">
    <div class="col-xl-6">
        <div class="card custom-card">
            <div class="card-header"><div class="card-title">Inventory &amp; Quality</div></div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    @include('admin.dashboard.partials.row', [
                        'label' => 'Quantity held in quarantine',
                        'value' => number_format($widgets['inventory']['quarantine_qty'], 4),
                    ])
                    @include('admin.dashboard.partials.row', [
                        'label' => 'Quantity in rejection stores',
                        'value' => number_format($widgets['inventory']['rejection_qty'], 4),
                    ])
                    @include('admin.dashboard.partials.row', [
                        'label' => 'Items below minimum stock',
                        'value' => number_format($widgets['inventory']['below_min_stock']),
                    ])
                    @include('admin.dashboard.partials.row', [
                        'label' => 'QC inspections awaiting result',
                        'value' => number_format($widgets['inventory']['pending_inspections']),
                    ])
                </ul>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card custom-card">
            <div class="card-header"><div class="card-title">Shop Floor</div></div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    @include('admin.dashboard.partials.row', [
                        'label' => 'Live work orders',
                        'value' => number_format($widgets['production']['live_orders']),
                    ])
                    @include('admin.dashboard.partials.row', [
                        'label' => 'Work orders due today or overdue',
                        'value' => number_format($widgets['production']['due_orders']),
                    ])
                    @include('admin.dashboard.partials.row', [
                        'label' => 'Machines under breakdown',
                        'value' => number_format($widgets['maintenance']['under_breakdown']),
                    ])
                    @include('admin.dashboard.partials.row', [
                        'label' => 'Preventive maintenance due',
                        'value' => number_format($widgets['maintenance']['pm_due']),
                    ])
                </ul>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card custom-card">
            <div class="card-header"><div class="card-title">Money</div></div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    @include('admin.dashboard.partials.row', [
                        'label' => 'Receivable outstanding',
                        'value' => '₹ '.number_format($widgets['finance']['receivable_total'], 2),
                    ])
                    @include('admin.dashboard.partials.row', [
                        'label' => 'Receivable over 30 days',
                        'value' => '₹ '.number_format($widgets['finance']['receivable_overdue'], 2),
                    ])
                    @include('admin.dashboard.partials.row', [
                        'label' => 'Payable outstanding',
                        'value' => '₹ '.number_format($widgets['finance']['payable_total'], 2),
                    ])
                    @include('admin.dashboard.partials.row', [
                        'label' => 'Purchase bills awaiting approval',
                        'value' => number_format($widgets['purchase']['bills_awaiting_approval']),
                    ])
                </ul>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card custom-card">
            <div class="card-header"><div class="card-title">Registers</div></div>
            <div class="card-body">
                @can('report.view')
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.reports.show', 'sales') }}" class="btn btn-primary-light btn-sm">Sales Register</a>
                    <a href="{{ route('admin.reports.show', 'purchase') }}" class="btn btn-primary-light btn-sm">Purchase Register</a>
                    <a href="{{ route('admin.reports.show', 'stock') }}" class="btn btn-primary-light btn-sm">Stock Movement</a>
                    <a href="{{ route('admin.reports.show', 'production') }}" class="btn btn-primary-light btn-sm">Production Register</a>
                </div>
                <p class="text-muted fs-12 mt-3 mb-0">Registers open on the current month and export to CSV.</p>
                @else
                <p class="text-muted mb-0">You do not have access to register reports.</p>
                @endcan
            </div>
        </div>
    </div>
</div>
@endsection
