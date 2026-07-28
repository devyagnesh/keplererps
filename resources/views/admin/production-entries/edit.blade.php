@extends('admin.layouts.app')
@section('title', 'Edit Production Entry')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Entry {{ $productionEntry->document_no }}</h1>
        <x-admin.module-intro />
        <p class="text-muted mb-0">
            {{ $productionEntry->posted_at ? 'Posted' : 'Draft' }}
            · WO {{ $productionEntry->workOrder?->document_no }}
        </p>
    </div>
    <div class="d-flex gap-2">
        @if (! $productionEntry->posted_at)
            @can('production_entry.update')
            <button type="button" class="btn btn-success btn-sm btn-post-entry" data-url="{{ route('admin.production-entries.post', $productionEntry) }}">Post to Stock</button>
            @endcan
        @endif
        <a href="{{ route('admin.production-entries.index') }}" class="btn btn-light btn-sm">Back</a>
    </div>
</div>
@include('admin.production-entries._form', [
    'action' => route('admin.production-entries.store'),
    'method' => 'POST',
    'productionEntry' => $productionEntry,
    'selectedWorkOrderId' => $productionEntry->work_order_id,
    'workOrder' => $productionEntry->workOrder,
])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/production-entries/form.js') }}"></script>
@endpush
