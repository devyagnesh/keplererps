@extends('admin.layouts.app')
@section('title', 'Create Sales Quotation')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Create Sales Quotation</h1>
        <x-admin.module-intro />
    </div>
    <a href="{{ route('admin.sales-quotations.index') }}" class="btn btn-light btn-sm">Back</a>
</div>
@include('admin.sales-quotations._form', ['action' => route('admin.sales-quotations.store'), 'method' => 'POST', 'salesQuotation' => null])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/sales-quotations/form.js') }}"></script>
@endpush
