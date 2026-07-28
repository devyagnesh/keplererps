@extends('admin.layouts.app')
@section('title', 'Edit Purchase Bill')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Bill {{ $purchaseBill->document_no }}</h1>
        <x-admin.module-intro />
        <p class="text-muted mb-0">
            {{ $purchaseBill->status->label() }} · {{ $purchaseBill->match_status->label() }}
            · GRN {{ $purchaseBill->goodsReceipt?->document_no }}
        </p>
    </div>
    <div class="d-flex gap-2">
        @if ($purchaseBill->status->isEditable())
            @can('purchase_bill.approve')
            <button type="button" class="btn btn-success btn-sm btn-approve-bill"
                data-url="{{ route('admin.purchase-bills.approve', $purchaseBill) }}"
                data-matched="{{ $purchaseBill->match_status->isMatched() ? 1 : 0 }}">Approve</button>
            @endcan
            @can('purchase_bill.update')
            <button type="button" class="btn btn-danger-light btn-sm btn-cancel-bill" data-url="{{ route('admin.purchase-bills.cancel', $purchaseBill) }}">Cancel Bill</button>
            @endcan
        @endif
        <a href="{{ route('admin.purchase-bills.index') }}" class="btn btn-light btn-sm">Back</a>
    </div>
</div>
@include('admin.purchase-bills._form', ['action' => route('admin.purchase-bills.update', $purchaseBill), 'method' => 'PUT', 'purchaseBill' => $purchaseBill])
@endsection
@push('scripts')
<script>window.purchaseBillLinesUrl = @json(route('admin.purchase-bills.billable-lines', ['goods_receipt' => 0]));</script>
<script src="{{ asset('assets/admin/js/admin/purchase-bills/form.js') }}"></script>
@endpush
