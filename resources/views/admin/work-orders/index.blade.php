@extends('admin.layouts.app')
@section('title', 'Work Orders')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Work Orders</h1>
        <x-admin.module-intro />
    </div>
    @can('work_order.create')
    <a href="{{ route('admin.work-orders.create') }}" class="btn btn-primary btn-sm">Add Work Order</a>
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
    </div>
    <div class="table-responsive">
        <table id="masterTable" class="table table-bordered text-nowrap w-100">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>WO No</th>
                    <th>Date</th>
                    <th>Item</th>
                    <th>Planned Qty</th>
                    <th>Good Qty</th>
                    <th>Status</th>
                    <th>Due Date</th>
                    <th>Action</th>
                </tr>
            </thead>
        </table>
    </div>
</div></div>
@endsection
@push('scripts')
<script>window.masterDataUrl = @json(route('admin.work-orders.data'));</script>
<script src="{{ asset('assets/admin/js/admin/work-orders/list.js') }}"></script>
@endpush
