@extends('admin.layouts.app')
@section('title', 'Edit Sales Quotation')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Quote {{ $salesQuotation->document_no }}</h1>
        <p class="text-muted mb-0">{{ $salesQuotation->status->label() }} · Total {{ number_format((float) $salesQuotation->grand_total, 2) }}</p>
    </div>
    <div class="d-flex gap-2">
        @if ($salesQuotation->status->value === 'draft')
            @can('sales_quotation.update')
            <button type="button" class="btn btn-outline-primary btn-sm btn-mark-sent" data-url="{{ route('admin.sales-quotations.mark-sent', $salesQuotation) }}">Mark Sent</button>
            @endcan
        @endif
        @if ($salesQuotation->status->value === 'sent')
            @can('sales_quotation.update')
            <button type="button" class="btn btn-success btn-sm btn-accept-quotation" data-url="{{ route('admin.sales-quotations.accept', $salesQuotation) }}">Accept</button>
            @endcan
        @endif
        @if ($salesQuotation->status->canConvert())
            @can('sales_quotation.update')
            <button type="button" class="btn btn-primary btn-sm btn-convert-quotation" data-url="{{ route('admin.sales-quotations.convert', $salesQuotation) }}">Convert to SO</button>
            @endcan
        @endif
        <a href="{{ route('admin.sales-quotations.index') }}" class="btn btn-light btn-sm">Back</a>
    </div>
</div>
@include('admin.sales-quotations._form', ['action' => route('admin.sales-quotations.update', $salesQuotation), 'method' => 'PUT', 'salesQuotation' => $salesQuotation])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/sales-quotations/form.js') }}"></script>
@endpush
