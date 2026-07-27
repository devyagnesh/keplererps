@extends('admin.layouts.app')
@section('title', 'Goods Receipts')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Goods Receipt Notes</h1>
    @can('goods_receipt.create')
    <a href="{{ route('admin.goods-receipts.create') }}" class="btn btn-primary btn-sm">Add GRN</a>
    @endcan
</div>
<div class="card custom-card"><div class="card-body"><div class="table-responsive">
<table id="masterTable" class="table table-bordered text-nowrap w-100">
<thead><tr><th>ID</th><th>GRN No</th><th>Date</th><th>PO</th><th>Supplier</th><th>Status</th><th>Action</th></tr></thead>
</table>
</div></div></div>
@endsection
@push('scripts')
<script>window.masterDataUrl = @json(route('admin.goods-receipts.data'));</script>
<script src="{{ asset('assets/admin/js/admin/goods-receipts/list.js') }}"></script>
@endpush
