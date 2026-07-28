@extends('admin.layouts.app')
@section('title', 'Sales Quotations')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Sales Quotations</h1>
        <x-admin.module-intro />
    </div>
    @can('sales_quotation.create')
    <a href="{{ route('admin.sales-quotations.create') }}" class="btn btn-primary btn-sm">Add Quotation</a>
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
            <select id="filterCustomer" class="form-select select2">
                <option value="">All customers</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}">{{ $customer->party_code }} — {{ $customer->party_name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="table-responsive">
        <table id="masterTable" class="table table-bordered text-nowrap w-100">
            <thead><tr><th>ID</th><th>Quote No</th><th>Date</th><th>Customer</th><th>Status</th><th>Total</th><th>Action</th></tr></thead>
        </table>
    </div>
</div></div>
@endsection
@push('scripts')
<script>window.masterDataUrl = @json(route('admin.sales-quotations.data'));</script>
<script src="{{ asset('assets/admin/js/admin/sales-quotations/list.js') }}"></script>
@endpush
