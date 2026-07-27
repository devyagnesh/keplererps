@extends('admin.layouts.app')
@section('title', 'Stock Transfers')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Stock Transfers</h1>
    <a href="{{ route('admin.stock-transfers.create') }}" class="btn btn-primary btn-sm">Add</a>
</div>
<div class="card custom-card"><div class="card-body"><div class="table-responsive">
<table id="masterTable" class="table table-bordered text-nowrap w-100"><thead><tr><th>ID</th><th>Document</th><th>Date</th><th>From</th><th>To</th><th>Status</th><th>Action</th></tr></thead></table>
</div></div></div>
@endsection
@push('scripts')
<script>window.masterDataUrl = @json(route('admin.stock-transfers.data'));</script>
<script src="{{ asset('assets/admin/js/admin/stock-transfer/list.js') }}"></script>
@endpush
