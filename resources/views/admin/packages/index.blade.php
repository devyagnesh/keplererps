@extends('admin.layouts.app')
@section('title', 'Packages')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Packages</h1>
        <x-admin.module-intro />
    </div>
    <div class="d-flex gap-2">
        @can('package.create')<a href="{{ route('admin.packages.pack') }}" class="btn btn-primary btn-sm">Pack & Label</a>@endcan
        @can('package.scan')<a href="{{ route('admin.packages.scan-form') }}" class="btn btn-primary-light btn-sm">Gate Scan</a>@endcan
    </div>
</div>
<div class="card custom-card"><div class="card-body">
<div class="row g-2 mb-3">
    <div class="col-md-3">
        <select id="filterStatus" class="form-select">
            <option value="">All statuses</option>
            @foreach (\App\Enums\PackageStatus::cases() as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select id="filterWarehouse" class="form-select">
            <option value="">All warehouses</option>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}">{{ $warehouse->code }} — {{ $warehouse->name }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="table-responsive">
<table id="masterTable" class="table table-bordered text-nowrap w-100">
<thead><tr><th>ID</th><th>Label No</th><th>Item</th><th>Packing Unit</th><th>Batch</th><th>Qty</th><th>Challan</th><th>Packed At</th><th>Status</th><th>Action</th></tr></thead>
</table>
</div></div></div>
@endsection
@push('scripts')
<script>window.masterDataUrl = @json(route('admin.packages.data'));</script>
<script src="{{ asset('assets/admin/js/admin/packages/list.js') }}"></script>
@endpush
