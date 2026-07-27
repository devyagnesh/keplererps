@extends('admin.layouts.app')
@section('title', 'Employees')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Employees</h1>
    @can('employee.create')
    <a href="{{ route('admin.employees.create') }}" class="btn btn-primary btn-sm">Add Employee</a>
    @endcan
</div>
<div class="card custom-card"><div class="card-body">
<div class="row g-2 mb-3">
    <div class="col-md-3">
        <select id="filterStatus" class="form-select">
            <option value="">All statuses</option>
            @foreach (\App\Enums\EmploymentStatus::cases() as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select id="filterShift" class="form-select">
            <option value="">All shifts</option>
            @foreach ($shifts as $shift)
                <option value="{{ $shift->id }}">{{ $shift->code }} — {{ $shift->name }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="table-responsive">
<table id="masterTable" class="table table-bordered text-nowrap w-100">
<thead><tr><th>ID</th><th>Code</th><th>Name</th><th>Designation</th><th>Department</th><th>Shift</th><th>Joined</th><th>Monthly Gross</th><th>Status</th><th>Action</th></tr></thead>
</table>
</div></div></div>
@endsection
@push('scripts')
<script>window.masterDataUrl = @json(route('admin.employees.data'));</script>
<script src="{{ asset('assets/admin/js/admin/employees/list.js') }}"></script>
@endpush
