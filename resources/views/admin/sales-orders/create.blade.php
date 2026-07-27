@extends('admin.layouts.app')
@section('title', 'Create Sales Order')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Create Sales Order</h1>
    <a href="{{ route('admin.sales-orders.index') }}" class="btn btn-light btn-sm">Back</a>
</div>
@include('admin.sales-orders._form', ['action' => route('admin.sales-orders.store'), 'method' => 'POST', 'salesOrder' => null])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/sales-orders/form.js') }}"></script>
@endpush
