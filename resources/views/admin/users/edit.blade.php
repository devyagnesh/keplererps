@extends('admin.layouts.app')
@section('title', 'Edit User')
@section('content')
<div class="my-4"><h1 class="page-title fw-semibold fs-18 mb-0">Edit User</h1></div>
@include('admin.users._form', ['userModel' => $userModel, 'action' => route('admin.users.update', $userModel), 'method' => 'PUT'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/user/user-form.js') }}"></script>
@endpush
