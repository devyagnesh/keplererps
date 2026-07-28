@extends('admin.layouts.app')
@section('title', 'Maintenance '.$order->document_no)
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">{{ $order->document_no }}</h1>
        <x-admin.module-intro />
        <div class="text-muted fs-12">{{ $order->order_type->label() }} · {{ $order->status->label() }}</div>
    </div>
    <div class="d-flex gap-2">
        @if ($order->status->isEditable())
            @can('maintenance_order.update')
            <button type="button" class="btn btn-warning btn-sm" id="btnIssueParts" data-url="{{ route('admin.maintenance-orders.issue-parts', $order) }}">Issue Parts</button>
            <button type="button" class="btn btn-success btn-sm" id="btnCloseOrder" data-url="{{ route('admin.maintenance-orders.close', $order) }}">Close</button>
            @endcan
        @endif
        <a href="{{ route('admin.maintenance-orders.index') }}" class="btn btn-light btn-sm">Back</a>
    </div>
</div>
@include('admin.maintenance-orders._form', [
    'action' => route('admin.maintenance-orders.update', $order),
    'method' => 'PUT',
    'order' => $order,
])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/maintenance-orders/form.js') }}"></script>
@endpush
