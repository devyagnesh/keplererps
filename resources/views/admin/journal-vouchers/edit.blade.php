@extends('admin.layouts.app')
@section('title', 'Edit Journal Voucher')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Voucher {{ $voucher->document_no }}</h1>
        <x-admin.module-intro />
        <p class="text-muted mb-0">
            {{ $voucher->voucher_type->label() }} · {{ $voucher->status->label() }}
            @if ($voucher->source_type)· auto-posted from a source document @endif
        </p>
    </div>
    <div class="d-flex gap-2">
        @if ($voucher->status->value === 'draft' && $voucher->source_type === null)
            @can('journal_voucher.post')
            <button type="button" class="btn btn-success btn-sm btn-post-voucher" data-url="{{ route('admin.journal-vouchers.post', $voucher) }}">Post to Ledger</button>
            @endcan
        @endif
        @if ($voucher->status->value !== 'cancelled')
            @can('journal_voucher.update')
            <button type="button" class="btn btn-danger-light btn-sm btn-cancel-voucher" data-url="{{ route('admin.journal-vouchers.cancel', $voucher) }}">Cancel Voucher</button>
            @endcan
        @endif
        <a href="{{ route('admin.journal-vouchers.index') }}" class="btn btn-light btn-sm">Back</a>
    </div>
</div>
@include('admin.journal-vouchers._form', ['action' => route('admin.journal-vouchers.update', $voucher), 'method' => 'PUT'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/journal-vouchers/form.js') }}"></script>
@endpush
