@extends('admin.layouts.app')
@section('title', 'Add Journal Voucher')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Add Journal Voucher</h1>
        <x-admin.module-intro />
    </div>
    <a href="{{ route('admin.journal-vouchers.index') }}" class="btn btn-light btn-sm">Back</a>
</div>
@include('admin.journal-vouchers._form', ['voucher' => null, 'action' => route('admin.journal-vouchers.store'), 'method' => 'POST'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/journal-vouchers/form.js') }}"></script>
@endpush
