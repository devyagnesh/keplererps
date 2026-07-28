@extends('admin.layouts.app')
@section('title', 'Stock Balances')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Stock Balances & Valuation</h1>
        <x-admin.module-intro />
    </div>
</div>
<div class="row mb-3">
    <div class="col-md-4"><div class="card custom-card"><div class="card-body"><div class="text-muted">Total Qty</div><div class="fs-20 fw-semibold" id="summaryQty">{{ number_format($summary['total_qty'], 4) }}</div></div></div></div>
    <div class="col-md-4"><div class="card custom-card"><div class="card-body"><div class="text-muted">Total Value</div><div class="fs-20 fw-semibold" id="summaryValue">{{ number_format($summary['total_value'], 2) }}</div></div></div></div>
    <div class="col-md-4"><div class="card custom-card"><div class="card-body"><div class="text-muted">Balance Lines</div><div class="fs-20 fw-semibold" id="summaryLines">{{ $summary['lines'] }}</div></div></div></div>
</div>
<div class="card custom-card"><div class="card-body">
<div class="row g-3 mb-3">
    <div class="col-md-4"><label class="form-label">Warehouse</label><select id="filterWarehouse" class="form-select"><option value="">All</option>@foreach ($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label">Category</label><select id="filterCategory" class="form-select"><option value="">All</option>@foreach ($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></div>
</div>
<div class="table-responsive">
<table id="balanceTable" class="table table-bordered text-nowrap w-100">
<thead><tr><th>ID</th><th>Code</th><th>Item</th><th>Warehouse</th><th>Batch</th><th>Qty</th><th>Committed</th><th>Available</th><th>Value</th></tr></thead>
</table>
</div></div></div>
@endsection
@push('scripts')
<script>
    window.balanceDataUrl = @json(route('admin.stock-balances.data'));
    window.balanceSummaryUrl = @json(route('admin.stock-balances.summary'));
</script>
<script src="{{ asset('assets/admin/js/admin/stock-balance/list.js') }}"></script>
@endpush
