@extends('admin.layouts.app')
@section('title', 'Production Plans')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Production Plans</h1>
        <x-admin.module-intro />
    </div>
    @can('production_plan.create')
    <a href="{{ route('admin.production-plans.create') }}" class="btn btn-primary btn-sm">Add Plan</a>
    @endcan
</div>
<div class="card custom-card"><div class="card-body">
<div class="row g-2 mb-3">
    <div class="col-md-3">
        <select id="filterStatus" class="form-select">
            <option value="">All statuses</option>
            @foreach (\App\Enums\DocumentStatus::cases() as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="table-responsive">
<table id="masterTable" class="table table-bordered text-nowrap w-100">
<thead><tr><th>ID</th><th>Plan No</th><th>Date</th><th>Horizon</th><th>Lines</th><th>Status</th><th>Action</th></tr></thead>
</table>
</div></div></div>
@endsection
@push('scripts')
<script>window.masterDataUrl = @json(route('admin.production-plans.data'));</script>
<script src="{{ asset('assets/admin/js/admin/production-plans/list.js') }}"></script>
@endpush
