@extends('admin.layouts.app')

@section('title', 'Edit Warehouse')

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Edit Warehouse</h1>
        <x-admin.module-intro />
    </div>
</div>
@include('admin.warehouses._form', ['warehouse' => $warehouse, 'action' => route('admin.warehouses.update', $warehouse), 'method' => 'PUT'])
@endsection

@push('scripts')
<script src="{{ asset('assets/admin/js/admin/warehouse/warehouse-form.js') }}"></script>
@endpush
