@extends('admin.layouts.app')
@section('title', 'Journal Vouchers')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Journal Vouchers</h1>
    @can('journal_voucher.create')
    <a href="{{ route('admin.journal-vouchers.create') }}" class="btn btn-primary btn-sm">Add Voucher</a>
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
    <div class="col-md-3">
        <select id="filterVoucherType" class="form-select">
            <option value="">All voucher types</option>
            @foreach (\App\Enums\VoucherType::cases() as $type)
                <option value="{{ $type->value }}">{{ $type->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3"><input type="date" id="filterDateFrom" class="form-control" placeholder="From"></div>
    <div class="col-md-3"><input type="date" id="filterDateTo" class="form-control" placeholder="To"></div>
</div>
<div class="table-responsive">
<table id="masterTable" class="table table-bordered text-nowrap w-100">
<thead><tr><th>ID</th><th>Voucher No</th><th>Date</th><th>Type</th><th>Reference</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
</table>
</div></div></div>
@endsection
@push('scripts')
<script>window.masterDataUrl = @json(route('admin.journal-vouchers.data'));</script>
<script src="{{ asset('assets/admin/js/admin/journal-vouchers/list.js') }}"></script>
@endpush
