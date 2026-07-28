@extends('admin.layouts.app')

@section('title', 'Add Branch')

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Add Branch</h1>
        <x-admin.module-intro />
    </div>
</div>
@include('admin.branches._form', ['branch' => null, 'action' => route('admin.branches.store'), 'method' => 'POST'])
@endsection

@push('scripts')
<script src="{{ asset('assets/admin/js/admin/branch/branch-form.js') }}"></script>
@endpush
