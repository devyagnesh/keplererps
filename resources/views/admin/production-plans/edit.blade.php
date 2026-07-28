@extends('admin.layouts.app')
@section('title', 'Edit Production Plan')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Plan {{ $productionPlan->document_no }}</h1>
        <x-admin.module-intro />
        <p class="text-muted mb-0">{{ $productionPlan->status->label() }} · {{ $productionPlan->plan_from_date->format('d M Y') }} — {{ $productionPlan->plan_to_date->format('d M Y') }}</p>
    </div>
    <div class="d-flex gap-2">
        @if ($productionPlan->status->value === 'draft')
            @can('production_plan.post')
            <button type="button" class="btn btn-success btn-sm btn-generate-plan" data-url="{{ route('admin.production-plans.generate', $productionPlan) }}">Generate Work Orders</button>
            @endcan
        @endif
        @if ($productionPlan->status->value !== 'cancelled')
            @can('production_plan.update')
            <button type="button" class="btn btn-danger-light btn-sm btn-cancel-plan" data-url="{{ route('admin.production-plans.cancel', $productionPlan) }}">Cancel Plan</button>
            @endcan
        @endif
        <a href="{{ route('admin.production-plans.index') }}" class="btn btn-light btn-sm">Back</a>
    </div>
</div>
@include('admin.production-plans._form', ['action' => route('admin.production-plans.update', $productionPlan), 'method' => 'PUT', 'productionPlan' => $productionPlan])
@if ($productionPlan->shortages->isNotEmpty())
<div class="card custom-card"><div class="card-body">
    <h6 class="mb-3">Component Requirement</h6>
    <div class="table-responsive">
    <table class="table table-bordered text-nowrap w-100">
        <thead><tr><th>Component</th><th>Required</th><th>Free Stock</th><th>Shortage</th></tr></thead>
        <tbody>
        @foreach ($productionPlan->shortages as $shortage)
            <tr>
                <td>{{ $shortage->item?->item_code }} — {{ $shortage->item?->item_name }}</td>
                <td>{{ number_format((float) $shortage->required_quantity, 4) }}</td>
                <td>{{ number_format((float) $shortage->available_quantity, 4) }}</td>
                <td class="{{ (float) $shortage->shortage_quantity > 0 ? 'text-danger fw-semibold' : '' }}">{{ number_format((float) $shortage->shortage_quantity, 4) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
    <p class="text-muted fs-12 mb-0">Shortages of posted plans appear on the purchase suggestions screen.</p>
</div></div>
@endif
@endsection
@push('scripts')
<script>window.productionPlanDemandUrl = @json(route('admin.production-plans.demand'));</script>
<script src="{{ asset('assets/admin/js/admin/production-plans/form.js') }}"></script>
@endpush
