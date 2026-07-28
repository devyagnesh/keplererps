@extends('admin.layouts.app')
@section('title', 'Add Purchase Bill')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Add Purchase Bill</h1>
        <x-admin.module-intro />
    </div>
    <a href="{{ route('admin.purchase-bills.index') }}" class="btn btn-light btn-sm">Back</a>
</div>
@include('admin.purchase-bills._form', ['action' => route('admin.purchase-bills.store'), 'method' => 'POST', 'purchaseBill' => null])
@endsection
@push('scripts')
<script>window.purchaseBillLinesUrl = @json(route('admin.purchase-bills.billable-lines', ['goods_receipt' => 0]));</script>
<script src="{{ asset('assets/admin/js/admin/purchase-bills/form.js') }}"></script>
@endpush
