@extends('admin.layouts.app')
@section('title', 'Edit Purchase Return')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Return {{ $purchaseReturn->document_no }}</h1>
        <x-admin.module-intro />
        <p class="text-muted mb-0">{{ $purchaseReturn->status->label() }} · GRN {{ $purchaseReturn->goodsReceipt?->document_no }}</p>
    </div>
    <div class="d-flex gap-2">
        @if ($purchaseReturn->status->value === 'draft')
            @can('purchase_return.post')
            <button type="button" class="btn btn-success btn-sm btn-post-return" data-url="{{ route('admin.purchase-returns.post', $purchaseReturn) }}">Post to Stock</button>
            @endcan
        @endif
        @if ($purchaseReturn->status->value !== 'cancelled')
            @can('purchase_return.update')
            <button type="button" class="btn btn-danger-light btn-sm btn-cancel-return" data-url="{{ route('admin.purchase-returns.cancel', $purchaseReturn) }}">Cancel Return</button>
            @endcan
        @endif
        <a href="{{ route('admin.purchase-returns.index') }}" class="btn btn-light btn-sm">Back</a>
    </div>
</div>
@include('admin.purchase-returns._form', ['action' => route('admin.purchase-returns.update', $purchaseReturn), 'method' => 'PUT', 'purchaseReturn' => $purchaseReturn])
@endsection
@push('scripts')
<script>window.purchaseReturnLinesUrl = @json(route('admin.purchase-returns.returnable-lines', ['goods_receipt' => 0]));</script>
<script src="{{ asset('assets/admin/js/admin/purchase-returns/form.js') }}"></script>
@endpush
