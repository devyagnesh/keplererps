@extends('admin.layouts.app')
@section('title', 'QC Templates')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">QC Templates</h1>
    @can('qc_template.create')
    <a href="{{ route('admin.qc-templates.create') }}" class="btn btn-primary btn-sm">Add Template</a>
    @endcan
</div>
<div class="card custom-card"><div class="card-body">
    <div class="row mb-3 g-2">
        <div class="col-md-3">
            <select id="filterActive" class="form-select">
                <option value="">All</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
        </div>
    </div>
    <div class="table-responsive">
        <table id="masterTable" class="table table-bordered text-nowrap w-100">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Sampling</th>
                    <th>Scope</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
        </table>
    </div>
</div></div>
@endsection
@push('scripts')
<script>window.masterDataUrl = @json(route('admin.qc-templates.data'));</script>
<script src="{{ asset('assets/admin/js/admin/qc-templates/list.js') }}"></script>
@endpush
