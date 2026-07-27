@extends('admin.layouts.app')

@section('title', 'Add Warehouse')

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Add Warehouse</h1>
</div>
@include('admin.warehouses._form', ['warehouse' => null, 'action' => route('admin.warehouses.store'), 'method' => 'POST'])
@endsection

@push('scripts')
<script src="{{ asset('assets/admin/js/admin/warehouse/warehouse-form.js') }}"></script>
@endpush
