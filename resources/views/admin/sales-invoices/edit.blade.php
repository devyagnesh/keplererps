@extends('admin.layouts.app')
@section('title', 'Edit Sales Invoice')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Invoice {{ $salesInvoice->document_no }}</h1>
        <x-admin.module-intro />
        <p class="text-muted mb-0">{{ $salesInvoice->status->label() }} · SO {{ $salesInvoice->salesOrder?->document_no }} · Total {{ number_format((float) $salesInvoice->grand_total, 2) }}</p>
    </div>
    <div class="d-flex gap-2">
        @if ($salesInvoice->status->value === 'draft')
            @can('sales_invoice.confirm')
            <button type="button" class="btn btn-success btn-sm btn-confirm-invoice" data-url="{{ route('admin.sales-invoices.confirm', $salesInvoice) }}">Confirm Invoice</button>
            @endcan
        @endif
        <a href="{{ route('admin.sales-invoices.index') }}" class="btn btn-light btn-sm">Back</a>
    </div>
</div>
@include('admin.sales-invoices._form', ['action' => route('admin.sales-invoices.update', $salesInvoice), 'method' => 'PUT', 'salesInvoice' => $salesInvoice])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/sales-invoices/form.js') }}"></script>
@endpush
