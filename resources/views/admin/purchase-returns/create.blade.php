@extends('admin.layouts.app')
@section('title', 'Add Purchase Return')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Add Purchase Return</h1>
    <a href="{{ route('admin.purchase-returns.index') }}" class="btn btn-light btn-sm">Back</a>
</div>
@include('admin.purchase-returns._form', ['action' => route('admin.purchase-returns.store'), 'method' => 'POST', 'purchaseReturn' => null])
@endsection
@push('scripts')
<script>window.purchaseReturnLinesUrl = @json(route('admin.purchase-returns.returnable-lines', ['goods_receipt' => 0]));</script>
<script src="{{ asset('assets/admin/js/admin/purchase-returns/form.js') }}"></script>
@endpush
