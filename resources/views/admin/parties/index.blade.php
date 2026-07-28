@extends('admin.layouts.app')

@section('title', 'Customers & Suppliers')

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Customers & Suppliers</h1>
        <x-admin.module-intro />
    </div>
    <a href="{{ route('admin.parties.create') }}" class="btn btn-primary btn-sm">Add Party</a>
    <a href="{{ route('admin.parties.import.index') }}" class="btn btn-light btn-sm ms-1">Import CSV</a>
</div>
<div class="row mb-3">
    <div class="col-md-3">
        <select id="filterPartyType" class="form-control">
            <option value="">All Types</option>
            @foreach($partyTypes as $type)
                <option value="{{ $type->value }}">{{ ucfirst($type->value) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select id="filterStatus" class="form-control">
            <option value="">All Statuses</option>
            @foreach($statuses as $status)
                <option value="{{ $status->value }}">{{ ucfirst($status->value) }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="card custom-card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="partyTable" class="table table-bordered text-nowrap w-100">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>GSTIN</th>
                        <th>State</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.partyDataUrl = @json(route('admin.parties.data'));
</script>
<script src="{{ asset('assets/admin/js/admin/party/party.js') }}"></script>
@endpush
