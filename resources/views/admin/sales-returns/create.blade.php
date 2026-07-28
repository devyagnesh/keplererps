@extends('admin.layouts.app')
@section('title', 'Add Sales Return')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Add Sales Return</h1>
        <x-admin.module-intro />
    </div>
    <a href="{{ route('admin.sales-returns.index') }}" class="btn btn-light btn-sm">Back</a>
</div>
@include('admin.sales-returns._form', ['action' => route('admin.sales-returns.store'), 'method' => 'POST', 'salesReturn' => null])
@endsection
@push('scripts')
<script>window.salesReturnLinesUrl = @json(route('admin.sales-returns.returnable-lines', ['sales_invoice' => 0]));</script>
<script src="{{ asset('assets/admin/js/admin/sales-returns/form.js') }}"></script>
@endpush
