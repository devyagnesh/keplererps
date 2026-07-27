@extends('admin.layouts.app')
@section('title', 'Stock Ledger')
@section('content')
<div class="my-4"><h1 class="page-title fw-semibold fs-18 mb-0">Stock Ledger</h1></div>
<div class="card custom-card"><div class="card-body">
<div class="row g-3 mb-3">
    <div class="col-md-3"><label class="form-label">Warehouse</label><select id="filterWarehouse" class="form-select"><option value="">All</option>@foreach ($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">Item</label><select id="filterItem" class="form-select select2"><option value="">All</option>@foreach ($items as $item)<option value="{{ $item->id }}">{{ $item->item_code }} — {{ $item->item_name }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">From</label><input type="date" id="filterDateFrom" class="form-control"></div>
    <div class="col-md-3"><label class="form-label">To</label><input type="date" id="filterDateTo" class="form-control"></div>
</div>
<div class="table-responsive">
<table id="ledgerTable" class="table table-bordered text-nowrap w-100">
<thead><tr><th>ID</th><th>Posted</th><th>Item</th><th>Warehouse</th><th>Batch</th><th>Type</th><th>In</th><th>Out</th><th>Rate</th><th>Value</th><th>Balance</th></tr></thead>
</table>
</div></div></div>
@endsection
@push('scripts')
<script>window.ledgerDataUrl = @json(route('admin.stock-ledger.data'));</script>
<script src="{{ asset('assets/admin/js/admin/stock-ledger/list.js') }}"></script>
@endpush
