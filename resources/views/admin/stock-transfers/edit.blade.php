@extends('admin.layouts.app')
@section('title', 'Edit Stock Transfer')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4">
    <h1 class="page-title fw-semibold fs-18 mb-0">Transfer {{ $stockTransfer->document_no }}</h1>
    @if ($stockTransfer->status->value === 'draft')
        <button type="button" class="btn btn-success btn-sm btn-post-doc" data-url="{{ route('admin.stock-transfers.post', $stockTransfer) }}">Post</button>
    @endif
</div>
@include('admin.stock-transfers._form', ['stockTransfer' => $stockTransfer, 'action' => route('admin.stock-transfers.update', $stockTransfer), 'method' => 'PUT'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/stock-transfer/form.js') }}"></script>
@endpush
