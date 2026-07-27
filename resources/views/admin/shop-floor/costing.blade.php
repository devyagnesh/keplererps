@extends('admin.layouts.app')
@section('title', 'Costing Sheet')
@section('content')
@php $wo = $sheet['work_order']; @endphp
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Costing — {{ $wo->document_no }}</h1>
    <a href="{{ route('admin.shop-floor.operator', ['work_centre_id' => $wo->work_centre_id]) }}" class="btn btn-light btn-sm">Back to Board</a>
</div>
<div class="row g-3">
    <div class="col-md-6">
        <div class="card custom-card h-100">
            <div class="card-header"><div class="card-title mb-0">Standard Cost</div></div>
            <div class="card-body">
                <p class="mb-1">Item: <strong>{{ $wo->item?->item_code }}</strong> — {{ $wo->item?->item_name }}</p>
                <p class="mb-1">Unit cost: ₹ {{ number_format($sheet['standard']['unit_cost'], 4) }}</p>
                <p class="mb-0">Total (planned qty): ₹ {{ number_format($sheet['standard']['total_cost'], 2) }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card custom-card h-100">
            <div class="card-header"><div class="card-title mb-0">Actual Cost</div></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><td>Material</td><td class="text-end">₹ {{ number_format($sheet['actual']['material_cost'], 2) }}</td></tr>
                    <tr><td>Machine</td><td class="text-end">₹ {{ number_format($sheet['actual']['machine_cost'], 2) }}</td></tr>
                    <tr><td>Labour</td><td class="text-end">₹ {{ number_format($sheet['actual']['labour_cost'], 2) }}</td></tr>
                    <tr><td>Overhead</td><td class="text-end">₹ {{ number_format($sheet['actual']['overhead_cost'], 2) }}</td></tr>
                    <tr class="fw-semibold"><td>Total</td><td class="text-end">₹ {{ number_format($sheet['actual']['total_cost'], 2) }}</td></tr>
                    <tr><td>Unit cost</td><td class="text-end">₹ {{ number_format($sheet['actual']['unit_cost'], 4) }}</td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="alert {{ $sheet['variance'] > 0 ? 'alert-warning' : 'alert-success' }} mb-0">
            Variance: ₹ {{ number_format($sheet['variance'], 2) }}
            ({{ number_format($sheet['variance_percent'], 2) }}%)
        </div>
    </div>
</div>
@endsection
