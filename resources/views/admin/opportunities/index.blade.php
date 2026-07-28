@extends('admin.layouts.app')
@section('title', 'Opportunities')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Opportunities</h1>
        <x-admin.module-intro />
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.opportunities.pipeline') }}" class="btn btn-primary-light btn-sm">Pipeline Board</a>
        @can('opportunity.create')
        <a href="{{ route('admin.opportunities.create') }}" class="btn btn-primary btn-sm">Add Opportunity</a>
        @endcan
    </div>
</div>
<div class="card custom-card"><div class="card-body">
<div class="row g-2 mb-3">
    <div class="col-md-3">
        <select id="filterStage" class="form-select">
            <option value="">All stages</option>
            @foreach (\App\Enums\OpportunityStage::cases() as $stage)
                <option value="{{ $stage->value }}">{{ $stage->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select id="filterOwner" class="form-select">
            <option value="">All owners</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3"><input type="date" id="filterDateFrom" class="form-control"></div>
    <div class="col-md-3"><input type="date" id="filterDateTo" class="form-control"></div>
</div>
<div class="table-responsive">
<table id="masterTable" class="table table-bordered text-nowrap w-100">
<thead><tr><th>ID</th><th>Opportunity No</th><th>Date</th><th>Title</th><th>Customer</th><th>Expected</th><th>Weighted</th><th>Close By</th><th>Owner</th><th>Stage</th><th>Action</th></tr></thead>
</table>
</div></div></div>
@endsection
@push('scripts')
<script>window.masterDataUrl = @json(route('admin.opportunities.data'));</script>
<script src="{{ asset('assets/admin/js/admin/opportunities/list.js') }}"></script>
@endpush
