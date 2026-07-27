@extends('admin.layouts.app')
@section('title', 'Add Role')
@section('content')
<div class="my-4"><h1 class="page-title fw-semibold fs-18 mb-0">Add Role</h1></div>
@include('admin.roles._form', ['role' => null, 'action' => route('admin.roles.store'), 'method' => 'POST'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/role/role-form.js') }}"></script>
@endpush
