@extends('admin.layouts.app')
@section('title', 'Financial Years')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Financial Years</h1>
    <a href="{{ route('admin.financial-years.create') }}" class="btn btn-primary btn-sm">Add</a>
</div>
<div class="card custom-card"><div class="card-body"><div class="table-responsive">
<table id="masterTable" class="table table-bordered text-nowrap w-100">
<thead><tr><th>ID</th><th>Code</th><th>Name</th><th>Starts</th><th>Ends</th><th>Current</th><th>Status</th><th>Action</th></tr></thead>
</table>
</div></div></div>
@endsection
@push('scripts')
<script>window.masterDataUrl = @json(route('admin.financial-years.data'));</script>
<script src="{{ asset('assets/admin/js/admin/financial-year/list.js') }}"></script>
@endpush
