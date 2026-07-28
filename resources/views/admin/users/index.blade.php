@extends('admin.layouts.app')
@section('title', 'Users')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Users</h1>
        <x-admin.module-intro />
    </div>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">Add User</a>
</div>
<div class="card custom-card"><div class="card-body"><div class="table-responsive">
<table id="userTable" class="table table-bordered text-nowrap w-100">
<thead><tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Roles</th><th>Branch</th><th>Status</th><th>Action</th></tr></thead>
</table>
</div></div></div>
@endsection
@push('scripts')
<script>window.userDataUrl = @json(route('admin.users.data'));</script>
<script src="{{ asset('assets/admin/js/admin/user/user.js') }}"></script>
@endpush
