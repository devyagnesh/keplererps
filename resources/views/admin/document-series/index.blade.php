@extends('admin.layouts.app')
@section('title', 'Document Number Series')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Document Number Series</h1>
        <x-admin.module-intro />
    </div>
    <a href="{{ route('admin.document-series.create') }}" class="btn btn-primary btn-sm">Add</a>
</div>
<div class="card custom-card"><div class="card-body"><div class="table-responsive">
<table id="masterTable" class="table table-bordered text-nowrap w-100">
<thead><tr><th>ID</th><th>Type</th><th>Prefix</th><th>FY</th><th>Branch</th><th>Next</th><th>Status</th><th>Action</th></tr></thead>
</table>
</div></div></div>
@endsection
@push('scripts')
<script>window.masterDataUrl = @json(route('admin.document-series.data'));</script>
<script src="{{ asset('assets/admin/js/admin/document-series/list.js') }}"></script>
@endpush
