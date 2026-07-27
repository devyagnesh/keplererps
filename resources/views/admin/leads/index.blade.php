@extends('admin.layouts.app')
@section('title', 'Leads')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Leads</h1>
    @can('lead.create')
    <a href="{{ route('admin.leads.create') }}" class="btn btn-primary btn-sm">Add Lead</a>
    @endcan
</div>

<div class="row gy-3 mb-1">
    @foreach (\App\Enums\LeadStatus::cases() as $status)
    <div class="col-6 col-md">
        <div class="card custom-card"><div class="card-body py-3">
            <span class="text-muted d-block fs-12">{{ $status->label() }}</span>
            <h4 class="mb-0">{{ $counts[$status->value] ?? 0 }}</h4>
        </div></div>
    </div>
    @endforeach
</div>

<div class="card custom-card"><div class="card-body">
<div class="row g-2 mb-3">
    <div class="col-md-2">
        <select id="filterStatus" class="form-select">
            <option value="">All statuses</option>
            @foreach (\App\Enums\LeadStatus::cases() as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select id="filterSource" class="form-select">
            <option value="">All sources</option>
            @foreach (\App\Enums\LeadSource::cases() as $source)
                <option value="{{ $source->value }}">{{ $source->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select id="filterOwner" class="form-select">
            <option value="">All owners</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><input type="date" id="filterDateFrom" class="form-control"></div>
    <div class="col-md-2"><input type="date" id="filterDateTo" class="form-control"></div>
    <div class="col-md-2 d-flex align-items-center">
        <div class="form-check mb-0">
            <input class="form-check-input" type="checkbox" id="filterDueOnly">
            <label class="form-check-label" for="filterDueOnly">Follow-up due</label>
        </div>
    </div>
</div>
<div class="table-responsive">
<table id="masterTable" class="table table-bordered text-nowrap w-100">
<thead><tr><th>ID</th><th>Lead No</th><th>Date</th><th>Company</th><th>Contact</th><th>Source</th><th>Est. Value</th><th>Next Follow-up</th><th>Owner</th><th>Status</th><th>Action</th></tr></thead>
</table>
</div></div></div>
@endsection
@push('scripts')
<script>window.masterDataUrl = @json(route('admin.leads.data'));</script>
<script src="{{ asset('assets/admin/js/admin/leads/list.js') }}"></script>
@endpush
