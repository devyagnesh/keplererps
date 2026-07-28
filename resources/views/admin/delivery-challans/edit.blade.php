@extends('admin.layouts.app')
@section('title', 'Edit Delivery Challan')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Challan {{ $deliveryChallan->document_no }}</h1>
        <x-admin.module-intro />
        <p class="text-muted mb-0">{{ $deliveryChallan->status->label() }} · SO {{ $deliveryChallan->salesOrder?->document_no }} · {{ $deliveryChallan->customer?->party_name }}</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if ($deliveryChallan->status->value === 'draft')
            @can('delivery_challan.update')
            <button type="button" class="btn btn-success btn-sm btn-dispatch-challan" data-url="{{ route('admin.delivery-challans.dispatch', $deliveryChallan) }}">Dispatch</button>
            @endcan
        @endif
        @if ($deliveryChallan->eway_required)
        <button type="button" class="btn btn-info btn-sm btn-eway-payload" data-url="{{ route('admin.delivery-challans.eway-payload', $deliveryChallan) }}">Download E-way JSON</button>
        @endif
        @if ($deliveryChallan->status->canInvoice())
            @can('sales_invoice.create')
            <a href="{{ route('admin.sales-invoices.create', ['delivery_challan_id' => $deliveryChallan->id]) }}" class="btn btn-primary btn-sm">Create Invoice</a>
            @endcan
        @endif
        <a href="{{ route('admin.delivery-challans.index') }}" class="btn btn-light btn-sm">Back</a>
    </div>
</div>
@include('admin.delivery-challans._form', ['action' => route('admin.delivery-challans.update', $deliveryChallan), 'method' => 'PUT', 'deliveryChallan' => $deliveryChallan])
@if ($deliveryChallan->status->value === 'dispatched')
<div class="card custom-card mt-3"><div class="card-body">
    <h6 class="mb-3">Proof of Delivery</h6>
    <form id="podForm" action="{{ route('admin.delivery-challans.mark-delivered', $deliveryChallan) }}" method="POST" enctype="multipart/form-data" novalidate>
        @csrf
        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Upload POD (JPG, PNG, PDF, max 5 MB) *</label>
                <input type="file" class="form-control" name="pod" accept=".jpg,.jpeg,.png,.pdf" required>
            </div>
            <div class="col-md-auto">
                @can('delivery_challan.update')
                <button type="submit" class="btn btn-success">Mark Delivered</button>
                @endcan
            </div>
        </div>
    </form>
</div></div>
@endif
@if ($deliveryChallan->status->value === 'delivered' && $deliveryChallan->pod_path)
<div class="card custom-card mt-3"><div class="card-body">
    <p class="mb-0 text-muted">Proof of delivery uploaded on {{ optional($deliveryChallan->delivered_at)->format('d M Y H:i') }}.</p>
</div></div>
@endif
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/delivery-challans/form.js') }}"></script>
@endpush
