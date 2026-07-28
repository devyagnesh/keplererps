@extends('admin.layouts.app')
@section('title', 'Edit Work Order')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">WO {{ $workOrder->document_no }}</h1>
        <x-admin.module-intro />
        <p class="text-muted mb-0">
            {{ $workOrder->status->label() }}
            · Planned {{ number_format((float) $workOrder->planned_quantity, 4) }}
            · Good {{ number_format((float) $workOrder->good_quantity, 4) }}
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if ($workOrder->status->canRelease())
            @can('work_order.update')
            <button type="button" class="btn btn-success btn-sm btn-release-wo" data-url="{{ route('admin.work-orders.release', $workOrder) }}">Release</button>
            @endcan
        @endif
        @if ($workOrder->status->canClose())
            @can('work_order.update')
            <button type="button" class="btn btn-warning btn-sm btn-close-wo" data-url="{{ route('admin.work-orders.close', $workOrder) }}">Close</button>
            @endcan
        @endif
        @if ($workOrder->status->canReceiveProduction())
            @can('production_entry.create')
            <a href="{{ route('admin.production-entries.create', ['work_order_id' => $workOrder->id]) }}" class="btn btn-outline-primary btn-sm">Record Production</a>
            @endcan
        @endif
        <a href="{{ route('admin.work-orders.index') }}" class="btn btn-light btn-sm">Back</a>
    </div>
</div>
@include('admin.work-orders._form', [
    'action' => route('admin.work-orders.update', $workOrder),
    'method' => 'PUT',
    'workOrder' => $workOrder,
])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/work-orders/form.js') }}"></script>
@endpush
