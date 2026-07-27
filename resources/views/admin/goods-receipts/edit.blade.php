@extends('admin.layouts.app')
@section('title', 'Edit GRN')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">GRN {{ $goodsReceipt->document_no }}</h1>
        <p class="text-muted mb-0">{{ $goodsReceipt->status->label() }} · PO {{ $goodsReceipt->purchaseOrder?->document_no }}</p>
    </div>
    <div class="d-flex gap-2">
        @if ($goodsReceipt->status->value === 'draft')
            @can('goods_receipt.post')
            <button type="button" class="btn btn-success btn-sm btn-post-grn" data-url="{{ route('admin.goods-receipts.post', $goodsReceipt) }}">Post to Stock</button>
            @endcan
        @endif
        @if ($goodsReceipt->status->value === 'posted')
            @can('purchase_bill.create')
            <a href="{{ route('admin.purchase-bills.create', ['goods_receipt_id' => $goodsReceipt->id]) }}" class="btn btn-primary btn-sm">Create Bill</a>
            @endcan
        @endif
        <a href="{{ route('admin.goods-receipts.index') }}" class="btn btn-light btn-sm">Back</a>
    </div>
</div>
@include('admin.goods-receipts._form', ['action' => route('admin.goods-receipts.update', $goodsReceipt), 'method' => 'PUT', 'goodsReceipt' => $goodsReceipt])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/goods-receipts/form.js') }}"></script>
@endpush
