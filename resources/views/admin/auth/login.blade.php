@extends('admin.layouts.auth')

@section('title', 'Sign In')

@section('content')
<div class="card custom-card">
    <div class="card-body p-5">
        <p class="h5 fw-semibold mb-2 text-center">Sign In</p>
        <p class="mb-4 text-muted op-7 fw-normal text-center">Manufacturing ERP — Kepler Soft</p>
        <form id="loginForm" action="{{ route('admin.login.submit') }}" method="POST" novalidate>
            @csrf
            <div class="row gy-3">
                <div class="col-xl-12">
                    <label for="login" class="form-label text-default">Email / Username / Mobile</label>
                    <input type="text" class="form-control form-control-lg" id="login" name="login" placeholder="Enter login ID" autocomplete="username">
                </div>
                <div class="col-xl-12 mb-2">
                    <label for="password" class="form-label text-default">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="Password" autocomplete="current-password">
                        <button class="btn btn-light" type="button" onclick="createpassword('password',this)"><i class="ri-eye-off-line align-middle"></i></button>
                    </div>
                </div>
                <div class="col-xl-12 d-grid mt-2">
                    <button type="submit" class="btn btn-lg btn-primary">Sign In</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/admin/js/admin/auth/login.js') }}"></script>
@endpush
