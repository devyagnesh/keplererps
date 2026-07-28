@extends('admin.layouts.app')
@section('title', 'Add Employee')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Add Employee</h1>
        <x-admin.module-intro />
    </div>
    <a href="{{ route('admin.employees.index') }}" class="btn btn-light btn-sm">Back</a>
</div>
@include('admin.employees._form', ['employee' => null, 'action' => route('admin.employees.store'), 'method' => 'POST'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/employees/form.js') }}"></script>
@endpush
