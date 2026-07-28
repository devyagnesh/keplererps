@extends('admin.layouts.app')
@section('title', 'Edit Employee')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">{{ $employee->employee_code }} — {{ $employee->full_name }}</h1>
        <x-admin.module-intro />
        <p class="text-muted mb-0">
            Basic {{ number_format($employee->basicAmount(), 2) }} · Allowances {{ number_format($employee->allowanceAmount(), 2) }}
        </p>
    </div>
    <a href="{{ route('admin.employees.index') }}" class="btn btn-light btn-sm">Back</a>
</div>
@include('admin.employees._form', ['action' => route('admin.employees.update', $employee), 'method' => 'PUT'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/employees/form.js') }}"></script>
@endpush
