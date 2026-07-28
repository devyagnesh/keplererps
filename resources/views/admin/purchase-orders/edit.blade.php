@extends('admin.layouts.app')
@section('title', 'Edit Purchase Order')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">PO {{ $purchaseOrder->document_no }}</h1>
        <x-admin.module-intro />
        <p class="text-muted mb-0">{{ $purchaseOrder->status->label() }} · Total {{ number_format((float) $purchaseOrder->grand_total, 2) }}</p>
    </div>
    <div class="d-flex gap-2">
        @if ($purchaseOrder->status->value === 'draft')
            @can('purchase_order.approve')
            <button type="button" class="btn btn-success btn-sm btn-approve-po" data-url="{{ route('admin.purchase-orders.approve', $purchaseOrder) }}">Approve</button>
            @endcan
        @endif
        @if ($purchaseOrder->status->value === 'approved')
            @can('purchase_order.update')
            <button type="button" class="btn btn-outline-primary btn-sm btn-mark-sent" data-url="{{ route('admin.purchase-orders.mark-sent', $purchaseOrder) }}">Mark Sent</button>
            @endcan
        @endif
        @if ($purchaseOrder->status->canReceive())
            @can('goods_receipt.create')
            <a href="{{ route('admin.goods-receipts.create', ['purchase_order_id' => $purchaseOrder->id]) }}" class="btn btn-primary btn-sm">Create GRN</a>
            @endcan
        @endif
        <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-light btn-sm">Back</a>
    </div>
</div>
@include('admin.purchase-orders._form', ['action' => route('admin.purchase-orders.update', $purchaseOrder), 'method' => 'PUT', 'purchaseOrder' => $purchaseOrder])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/purchase-orders/form.js') }}"></script>
@endpush
