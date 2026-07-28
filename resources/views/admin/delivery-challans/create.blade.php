@extends('admin.layouts.app')
@section('title', 'Create Delivery Challan')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Create Delivery Challan</h1>
        <x-admin.module-intro />
    </div>
    <a href="{{ route('admin.delivery-challans.index') }}" class="btn btn-light btn-sm">Back</a>
</div>
@include('admin.delivery-challans._form', ['action' => route('admin.delivery-challans.store'), 'method' => 'POST', 'deliveryChallan' => null])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/delivery-challans/form.js') }}"></script>
@endpush
