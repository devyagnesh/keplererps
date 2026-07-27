@extends('admin.layouts.app')
@section('title', 'Sales Returns')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Sales Returns</h1>
    @can('sales_return.create')
    <a href="{{ route('admin.sales-returns.create') }}" class="btn btn-primary btn-sm">Add Return</a>
    @endcan
</div>
<div class="card custom-card"><div class="card-body">
<div class="row g-2 mb-3">
    <div class="col-md-3">
        <select id="filterStatus" class="form-select">
            <option value="">All statuses</option>
            @foreach (\App\Enums\DocumentStatus::cases() as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="table-responsive">
<table id="masterTable" class="table table-bordered text-nowrap w-100">
<thead><tr><th>ID</th><th>Return No</th><th>Date</th><th>Customer</th><th>Invoice</th><th>Reason</th><th>Total</th><th>Status</th><th>Action</th></tr></thead>
</table>
</div></div></div>
@endsection
@push('scripts')
<script>window.masterDataUrl = @json(route('admin.sales-returns.data'));</script>
<script src="{{ asset('assets/admin/js/admin/sales-returns/list.js') }}"></script>
@endpush
