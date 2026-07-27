@extends('admin.layouts.app')
@section('title', 'Create GRN')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Create Goods Receipt</h1>
    <a href="{{ route('admin.goods-receipts.index') }}" class="btn btn-light btn-sm">Back</a>
</div>
@include('admin.goods-receipts._form', ['action' => route('admin.goods-receipts.store'), 'method' => 'POST', 'goodsReceipt' => null])
@endsection
@push('scripts')
<script>
window.grnPendingLinesUrl = @json(url('/admin/goods-receipts/pending-lines'));
</script>
<script src="{{ asset('assets/admin/js/admin/goods-receipts/form.js') }}"></script>
@endpush
