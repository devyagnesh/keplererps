@extends('admin.layouts.app')

@section('title', 'Warehouses')

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Warehouses</h1>
        <x-admin.module-intro />
    </div>
    <a href="{{ route('admin.warehouses.create') }}" class="btn btn-primary btn-sm">Add Warehouse</a>
</div>
<div class="row mb-3">
    <div class="col-md-3">
        <select id="filterBranch" class="form-control select2">
            <option value="">All Branches</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select id="filterLevel" class="form-control">
            <option value="">All Levels</option>
            @foreach($levels as $level)
                <option value="{{ $level->value }}">{{ ucfirst($level->value) }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="card custom-card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="warehouseTable" class="table table-bordered text-nowrap w-100">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Branch</th>
                        <th>Parent</th>
                        <th>Level</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.warehouseDataUrl = @json(route('admin.warehouses.data'));
</script>
<script src="{{ asset('assets/admin/js/admin/warehouse/warehouse.js') }}"></script>
@endpush
