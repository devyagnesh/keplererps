@extends('admin.layouts.app')
@section('title', 'Roles')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Roles & Permissions</h1>
        <x-admin.module-intro />
    </div>
    <a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm">Add Role</a>
</div>
<div class="card custom-card"><div class="card-body"><div class="table-responsive">
<table id="roleTable" class="table table-bordered text-nowrap w-100">
<thead><tr><th>ID</th><th>Name</th><th>Slug</th><th>Permissions</th><th>Type</th><th>Status</th><th>Action</th></tr></thead>
</table>
</div></div></div>
@endsection
@push('scripts')
<script>window.roleDataUrl = @json(route('admin.roles.data'));</script>
<script src="{{ asset('assets/admin/js/admin/role/role.js') }}"></script>
@endpush
