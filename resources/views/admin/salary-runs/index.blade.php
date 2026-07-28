@extends('admin.layouts.app')
@section('title', 'Salary Runs')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Salary Runs</h1>
        <x-admin.module-intro />
    </div>
    @can('salary_run.create')
    <a href="{{ route('admin.salary-runs.create') }}" class="btn btn-primary btn-sm">New Salary Run</a>
    @endcan
</div>
<div class="card custom-card"><div class="card-body">
<div class="row g-2 mb-3">
    <div class="col-md-3">
        <select id="filterStatus" class="form-select">
            <option value="">All statuses</option>
            @foreach (\App\Enums\SalaryRunStatus::cases() as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <input type="number" id="filterYear" class="form-control" placeholder="Year" min="2000" max="2100">
    </div>
</div>
<div class="table-responsive">
<table id="masterTable" class="table table-bordered text-nowrap w-100">
<thead><tr><th>ID</th><th>Run No</th><th>Period</th><th>Payment Date</th><th>Employees</th><th>Gross</th><th>Deductions</th><th>Net</th><th>Status</th><th>Action</th></tr></thead>
</table>
</div></div></div>
@endsection
@push('scripts')
<script>window.masterDataUrl = @json(route('admin.salary-runs.data'));</script>
<script src="{{ asset('assets/admin/js/admin/salary-runs/list.js') }}"></script>
@endpush
