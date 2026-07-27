@extends('admin.layouts.app')
@section('title', 'Edit Sales Return')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Return {{ $salesReturn->document_no }}</h1>
        <p class="text-muted mb-0">{{ $salesReturn->status->label() }} · Invoice {{ $salesReturn->salesInvoice?->document_no ?? '—' }}</p>
    </div>
    <div class="d-flex gap-2">
        @if ($salesReturn->status->value === 'draft')
            @can('sales_return.post')
            <button type="button" class="btn btn-success btn-sm btn-post-return" data-url="{{ route('admin.sales-returns.post', $salesReturn) }}">Post to Stock</button>
            @endcan
        @endif
        @if ($salesReturn->status->value !== 'cancelled')
            @can('sales_return.update')
            <button type="button" class="btn btn-danger-light btn-sm btn-cancel-return" data-url="{{ route('admin.sales-returns.cancel', $salesReturn) }}">Cancel Return</button>
            @endcan
        @endif
        <a href="{{ route('admin.sales-returns.index') }}" class="btn btn-light btn-sm">Back</a>
    </div>
</div>
@include('admin.sales-returns._form', ['action' => route('admin.sales-returns.update', $salesReturn), 'method' => 'PUT', 'salesReturn' => $salesReturn])
@endsection
@push('scripts')
<script>window.salesReturnLinesUrl = @json(route('admin.sales-returns.returnable-lines', ['sales_invoice' => 0]));</script>
<script src="{{ asset('assets/admin/js/admin/sales-returns/form.js') }}"></script>
@endpush
