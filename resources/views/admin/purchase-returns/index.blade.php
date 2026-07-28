@extends('admin.layouts.app')
@section('title', 'Purchase Returns')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Purchase Returns</h1>
        <x-admin.module-intro />
    </div>
    @can('purchase_return.create')
    <a href="{{ route('admin.purchase-returns.create') }}" class="btn btn-primary btn-sm">Add Return</a>
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
<thead><tr><th>ID</th><th>Return No</th><th>Date</th><th>Supplier</th><th>GRN</th><th>Reason</th><th>Total</th><th>Status</th><th>Action</th></tr></thead>
</table>
</div></div></div>
@endsection
@push('scripts')
<script>window.masterDataUrl = @json(route('admin.purchase-returns.data'));</script>
<script src="{{ asset('assets/admin/js/admin/purchase-returns/list.js') }}"></script>
@endpush
