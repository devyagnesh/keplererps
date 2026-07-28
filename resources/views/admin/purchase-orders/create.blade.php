@extends('admin.layouts.app')
@section('title', 'Create Purchase Order')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Create Purchase Order</h1>
        <x-admin.module-intro />
    </div>
    <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-light btn-sm">Back</a>
</div>
@include('admin.purchase-orders._form', ['action' => route('admin.purchase-orders.store'), 'method' => 'POST', 'purchaseOrder' => null])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/purchase-orders/form.js') }}"></script>
@endpush
