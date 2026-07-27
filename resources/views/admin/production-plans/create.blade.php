@extends('admin.layouts.app')
@section('title', 'Add Production Plan')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Add Production Plan</h1>
    <a href="{{ route('admin.production-plans.index') }}" class="btn btn-light btn-sm">Back</a>
</div>
@include('admin.production-plans._form', ['action' => route('admin.production-plans.store'), 'method' => 'POST', 'productionPlan' => null])
@endsection
@push('scripts')
<script>window.productionPlanDemandUrl = @json(route('admin.production-plans.demand'));</script>
<script src="{{ asset('assets/admin/js/admin/production-plans/form.js') }}"></script>
@endpush
