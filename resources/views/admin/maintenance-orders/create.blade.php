@extends('admin.layouts.app')
@section('title', 'New Maintenance Order')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">New Maintenance Order</h1>
        <x-admin.module-intro />
    </div>
    <a href="{{ route('admin.maintenance-orders.index') }}" class="btn btn-light btn-sm">Back</a>
</div>
@include('admin.maintenance-orders._form', [
    'action' => route('admin.maintenance-orders.store'),
    'method' => 'POST',
    'order' => null,
])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/maintenance-orders/form.js') }}"></script>
@endpush
