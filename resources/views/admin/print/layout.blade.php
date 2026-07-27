<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') — {{ config('app.name', 'Kepler ERP') }}</title>
    <link href="{{ asset('assets/admin/libs/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/admin/css/print.css') }}" rel="stylesheet">
</head>
<body class="print-body">
    <div class="print-toolbar d-print-none">
        <button type="button" class="btn btn-primary btn-sm" id="btnPrintDocument">Print</button>
        <a href="{{ url()->previous() }}" class="btn btn-light btn-sm">Back</a>
    </div>
    <div class="print-sheet">
        @yield('content')
    </div>
    <script src="{{ asset('assets/admin/lib/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/admin/print/print.js') }}"></script>
    @stack('scripts')
</body>
</html>
