@extends('admin.layouts.app')
@section('title', 'Add User')
@section('content')
<div class="my-4"><h1 class="page-title fw-semibold fs-18 mb-0">Add User</h1></div>
@include('admin.users._form', ['userModel' => null, 'action' => route('admin.users.store'), 'method' => 'POST'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/user/user-form.js') }}"></script>
@endpush
