@extends('admin.layouts.app')
@section('title', 'Delivery Challans')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Delivery Challans</h1>
        <x-admin.module-intro />
    </div>
    @can('delivery_challan.create')
    <a href="{{ route('admin.delivery-challans.create') }}" class="btn btn-primary btn-sm">Add Challan</a>
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
            <thead><tr><th>Challan No</th><th>Date</th><th>Sales Order</th><th>Customer</th><th>Vehicle</th><th>Status</th><th>Action</th></tr></thead>
        </table>
    </div>
</div></div>
@endsection
@push('scripts')
<script>window.masterDataUrl = @json(route('admin.delivery-challans.data'));</script>
<script src="{{ asset('assets/admin/js/admin/delivery-challans/list.js') }}"></script>
@endpush
