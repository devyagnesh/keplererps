@extends('admin.layouts.app')
@section('title', 'Edit Role')
@section('content')
<div class="my-4"><h1 class="page-title fw-semibold fs-18 mb-0">Edit Role</h1></div>
@include('admin.roles._form', ['role' => $role, 'action' => route('admin.roles.update', $role), 'method' => 'PUT'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/role/role-form.js') }}"></script>
@endpush
