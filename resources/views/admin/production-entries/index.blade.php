@extends('admin.layouts.app')
@section('title', 'Production Entries')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Production Entries</h1>
        <x-admin.module-intro />
    </div>
    @can('production_entry.create')
    <a href="{{ route('admin.production-entries.create') }}" class="btn btn-primary btn-sm">Add Entry</a>
    @endcan
</div>
<div class="card custom-card"><div class="card-body">
    <div class="row mb-3 g-2">
        <div class="col-md-4">
            <select id="filterWorkOrder" class="form-select select2">
                <option value="">All work orders</option>
                @foreach ($workOrders as $workOrder)
                    <option value="{{ $workOrder->id }}">{{ $workOrder->document_no }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="table-responsive">
        <table id="masterTable" class="table table-bordered text-nowrap w-100">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Entry No</th>
                    <th>Date</th>
                    <th>Work Order</th>
                    <th>Item</th>
                    <th>Good Qty</th>
                    <th>Rejected Qty</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
        </table>
    </div>
</div></div>
@endsection
@push('scripts')
<script>window.masterDataUrl = @json(route('admin.production-entries.data'));</script>
<script src="{{ asset('assets/admin/js/admin/production-entries/list.js') }}"></script>
@endpush
