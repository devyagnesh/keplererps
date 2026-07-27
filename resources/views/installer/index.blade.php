<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install — {{ config('app.name', 'Kepler ERP') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/admin/css/style.css') }}">
</head>
<body class="authentication-background">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title mb-0">Application Installer</div>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif
                    <p class="text-muted">Enter the installation key from your <code>.env</code> file to run migrations and seed core data.</p>
                    <form method="POST" action="{{ url('/install') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="install_key" class="form-label">Installation Key</label>
                            <input type="password" name="install_key" id="install_key" class="form-control" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Run Installation</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
