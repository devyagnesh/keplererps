<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-vertical-style="overlay" data-theme-mode="light" data-header-styles="light" data-menu-styles="light" data-toggled="close">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign In') — {{ config('app.name', 'Kepler ERP') }}</title>
    <link rel="icon" href="{{ asset('assets/admin/images/brand-logos/favicon.ico') }}" type="image/x-icon">
    <script src="{{ asset('assets/admin/js/authentication-main.js') }}"></script>
    <link id="style" href="{{ asset('assets/admin/libs/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/admin/css/styles.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/admin/css/icons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/admin/lib/toastify/toastify.min.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center authentication authentication-basic h-100">
            <div class="col-xxl-4 col-xl-5 col-lg-5 col-md-6 col-sm-8 col-12">
                <div class="my-5 d-flex justify-content-center">
                    <a href="{{ route('admin.login') }}">
                        <img src="{{ asset('assets/admin/images/brand-logos/desktop-logo.png') }}" alt="logo" class="desktop-logo">
                        <img src="{{ asset('assets/admin/images/brand-logos/desktop-dark.png') }}" alt="logo" class="desktop-dark">
                    </a>
                </div>
                @yield('content')
            </div>
        </div>
    </div>
    <script src="{{ asset('assets/admin/lib/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/admin/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/admin/lib/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('assets/admin/lib/jquery-validation/additional-methods.min.js') }}"></script>
    <script src="{{ asset('assets/admin/lib/toastify/toastify.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/show-password.js') }}"></script>
    <script src="{{ asset('assets/admin/js/admin/core/ajax-setup.js') }}"></script>
    <script src="{{ asset('assets/admin/js/admin/core/notifications.js') }}"></script>
    <script src="{{ asset('assets/admin/js/admin/core/form-helpers.js') }}"></script>
    @stack('scripts')
</body>
</html>
