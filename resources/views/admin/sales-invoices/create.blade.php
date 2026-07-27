@extends('admin.layouts.app')
@section('title', 'Create Sales Invoice')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Create Sales Invoice</h1>
    <a href="{{ route('admin.sales-invoices.index') }}" class="btn btn-light btn-sm">Back</a>
</div>
@include('admin.sales-invoices._form', ['action' => route('admin.sales-invoices.store'), 'method' => 'POST', 'salesInvoice' => null])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/sales-invoices/form.js') }}"></script>
@endpush
