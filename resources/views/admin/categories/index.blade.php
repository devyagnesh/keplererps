@extends('admin.layouts.app')
@section('title', 'Categories')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Categories</h1>
        <x-admin.module-intro />
    </div>
    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm">Add</a>
</div>
<div class="card custom-card"><div class="card-body"><div class="table-responsive">
<table id="masterTable" class="table table-bordered text-nowrap w-100"><thead><tr><th>ID</th><th>Code</th><th>Name</th><th>Parent</th><th>Type</th><th>Status</th><th>Action</th></tr></thead></table>
</div></div></div>
@endsection
@push('scripts')
<script>window.masterDataUrl = @json(route('admin.categories.data')); window.masterDeleteSelector = '.btn-delete-master';</script>
<script src="{{ asset('assets/admin/js/admin/category/list.js') }}"></script>
@endpush
