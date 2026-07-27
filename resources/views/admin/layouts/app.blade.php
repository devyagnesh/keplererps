<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="close">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name', 'Kepler ERP') }}</title>
    <link rel="icon" href="{{ asset('assets/admin/images/brand-logos/favicon.ico') }}" type="image/x-icon">
    <script src="{{ asset('assets/admin/libs/choices.js/public/assets/scripts/choices.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/main.js') }}"></script>
    <link id="style" href="{{ asset('assets/admin/libs/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/admin/css/styles.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/admin/css/icons.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/admin/libs/node-waves/waves.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/admin/libs/simplebar/simplebar.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/admin/libs/flatpickr/flatpickr.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/admin/libs/choices.js/public/assets/styles/choices.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/admin/lib/toastify/toastify.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/admin/lib/select2/select2.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/admin/lib/datatables/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/admin/lib/datatables/responsive.bootstrap5.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/admin/lib/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
    <div id="loader">
        <img src="{{ asset('assets/admin/images/media/loader.svg') }}" alt="">
    </div>
    <div class="page">
        @include('admin.partials.header')
        @include('admin.partials.sidebar')
        <div class="main-content app-content">
            <div class="container-fluid">
                @yield('content')
            </div>
        </div>
        @include('admin.partials.footer')
    </div>
    <div class="scrollToTop">
        <span class="arrow"><i class="ri-arrow-up-s-fill fs-20"></i></span>
    </div>
    <div id="responsive-overlay"></div>

    <script src="{{ asset('assets/admin/lib/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/admin/libs/@popperjs/core/umd/popper.min.js') }}"></script>
    <script src="{{ asset('assets/admin/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/defaultmenu.min.js') }}"></script>
    <script src="{{ asset('assets/admin/libs/node-waves/waves.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/sticky.js') }}"></script>
    <script src="{{ asset('assets/admin/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/simplebar.js') }}"></script>
    <script src="{{ asset('assets/admin/lib/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('assets/admin/lib/jquery-validation/additional-methods.min.js') }}"></script>
    <script src="{{ asset('assets/admin/lib/toastify/toastify.min.js') }}"></script>
    <script src="{{ asset('assets/admin/lib/select2/select2.min.js') }}"></script>
    <script src="{{ asset('assets/admin/lib/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/admin/lib/datatables/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/admin/lib/datatables/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('assets/admin/lib/datatables/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('assets/admin/lib/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('assets/admin/lib/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/custom.js') }}"></script>
    <script src="{{ asset('assets/admin/js/admin/core/ajax-setup.js') }}"></script>
    <script src="{{ asset('assets/admin/js/admin/core/notifications.js') }}"></script>
    <script src="{{ asset('assets/admin/js/admin/core/datatable.js') }}"></script>
    <script src="{{ asset('assets/admin/js/admin/core/form-helpers.js') }}"></script>
    @stack('scripts')
</body>
</html>
