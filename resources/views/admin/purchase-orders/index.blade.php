@extends('admin.layouts.app')
@section('title', 'Purchase Orders')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Purchase Orders</h1>
        <x-admin.module-intro />
    </div>
    @can('purchase_order.create')
    <a href="{{ route('admin.purchase-orders.create') }}" class="btn btn-primary btn-sm">Add PO</a>
    @endcan
</div>
<div class="card custom-card"><div class="card-body">
    <div class="row mb-3 g-2">
        <div class="col-md-3">
            <select id="filterStatus" class="form-select">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <select id="filterSupplier" class="form-select select2">
                <option value="">All suppliers</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->party_code }} — {{ $supplier->party_name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="table-responsive">
        <table id="masterTable" class="table table-bordered text-nowrap w-100">
            <thead><tr><th>ID</th><th>PO No</th><th>Date</th><th>Supplier</th><th>Warehouse</th><th>Status</th><th>Total</th><th>Action</th></tr></thead>
        </table>
    </div>
</div></div>
@endsection
@push('scripts')
<script>window.masterDataUrl = @json(route('admin.purchase-orders.data'));</script>
<script src="{{ asset('assets/admin/js/admin/purchase-orders/list.js') }}"></script>
@endpush
