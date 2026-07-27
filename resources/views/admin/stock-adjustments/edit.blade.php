@extends('admin.layouts.app')
@section('title', 'Edit Stock Adjustment')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4">
    <h1 class="page-title fw-semibold fs-18 mb-0">Adjustment {{ $stockAdjustment->document_no }}</h1>
    @if ($stockAdjustment->status->value === 'draft')
        <button type="button" class="btn btn-success btn-sm btn-post-doc" data-url="{{ route('admin.stock-adjustments.post', $stockAdjustment) }}">Post</button>
    @endif
</div>
@include('admin.stock-adjustments._form', ['stockAdjustment' => $stockAdjustment, 'action' => route('admin.stock-adjustments.update', $stockAdjustment), 'method' => 'PUT'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/stock-adjustment/form.js') }}"></script>
@endpush
