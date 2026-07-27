@extends('admin.layouts.app')
@section('title', 'Edit Opening Stock')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4">
    <h1 class="page-title fw-semibold fs-18 mb-0">Opening Stock {{ $openingStock->document_no }}</h1>
    @if ($openingStock->status->value === 'draft')
        <button type="button" class="btn btn-success btn-sm btn-post-doc" data-url="{{ route('admin.opening-stocks.post', $openingStock) }}">Post to Ledger</button>
    @endif
</div>
@include('admin.opening-stocks._form', ['openingStock' => $openingStock, 'action' => route('admin.opening-stocks.update', $openingStock), 'method' => 'PUT'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/opening-stock/form.js') }}"></script>
@endpush
