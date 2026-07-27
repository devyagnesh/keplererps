@extends('admin.layouts.app')

@section('title', 'Edit Branch')

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Edit Branch</h1>
</div>
@include('admin.branches._form', ['branch' => $branch, 'action' => route('admin.branches.update', $branch), 'method' => 'PUT'])
@endsection

@push('scripts')
<script src="{{ asset('assets/admin/js/admin/branch/branch-form.js') }}"></script>
@endpush
