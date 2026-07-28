@extends('admin.layouts.app')
@section('title', 'Chart of Accounts')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Chart of Accounts</h1>
        <x-admin.module-intro />
    </div>
    @can('ledger_account.create')
    <a href="{{ route('admin.ledger-accounts.create') }}" class="btn btn-primary btn-sm">Add Account</a>
    @endcan
</div>
<div class="card custom-card"><div class="card-body">
<div class="row g-2 mb-3">
    <div class="col-md-3">
        <select id="filterAccountType" class="form-select">
            <option value="">All account types</option>
            @foreach (\App\Enums\LedgerAccountType::cases() as $type)
                <option value="{{ $type->value }}">{{ $type->label() }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="table-responsive">
<table id="masterTable" class="table table-bordered text-nowrap w-100">
<thead><tr><th>ID</th><th>Code</th><th>Name</th><th>Type</th><th>Group</th><th>Parent</th><th>Opening</th><th>Status</th><th>Action</th></tr></thead>
</table>
</div></div></div>
@endsection
@push('scripts')
<script>window.masterDataUrl = @json(route('admin.ledger-accounts.data'));</script>
<script src="{{ asset('assets/admin/js/admin/ledger-accounts/list.js') }}"></script>
@endpush
