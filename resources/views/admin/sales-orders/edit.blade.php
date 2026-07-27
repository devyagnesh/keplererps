@extends('admin.layouts.app')
@section('title', 'Edit Sales Order')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">SO {{ $salesOrder->document_no }}</h1>
        <p class="text-muted mb-0">{{ $salesOrder->status->label() }} · Total {{ number_format((float) $salesOrder->grand_total, 2) }}</p>
    </div>
    <div class="d-flex gap-2">
        @if (in_array($salesOrder->status->value, ['draft', 'pending_approval'], true))
            @can('sales_order.update')
            <button type="button" class="btn btn-success btn-sm btn-confirm-so" data-url="{{ route('admin.sales-orders.confirm', $salesOrder) }}">Confirm</button>
            @endcan
        @endif
        @if ($salesOrder->status->isCancellable())
            @can('sales_order.update')
            <button type="button" class="btn btn-outline-danger btn-sm btn-cancel-so" data-url="{{ route('admin.sales-orders.cancel', $salesOrder) }}">Cancel</button>
            @endcan
        @endif
        <a href="{{ route('admin.sales-orders.index') }}" class="btn btn-light btn-sm">Back</a>
    </div>
</div>
@include('admin.sales-orders._form', ['action' => route('admin.sales-orders.update', $salesOrder), 'method' => 'PUT', 'salesOrder' => $salesOrder])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/sales-orders/form.js') }}"></script>
@endpush
