@extends('admin.layouts.app')
@section('title', 'Create Work Order')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Create Work Order</h1>
        <x-admin.module-intro />
    </div>
    <a href="{{ route('admin.work-orders.index') }}" class="btn btn-light btn-sm">Back</a>
</div>
@include('admin.work-orders._form', [
    'action' => route('admin.work-orders.store'),
    'method' => 'POST',
    'workOrder' => null,
])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/work-orders/form.js') }}"></script>
@endpush
