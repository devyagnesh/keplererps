<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Update — {{ config('app.name', 'Kepler ERP') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/admin/css/style.css') }}">
</head>
<body class="authentication-background">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title mb-0">Application Update</div>
                </div>
                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif
                    <p class="text-muted">Run pending migrations, clear caches, and refresh the public storage link. Super Admins may update without a key; others must supply the installation key.</p>
                    <form method="POST" action="{{ route('install.update.run') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="install_key" class="form-label">Installation Key (if not logged in as Super Admin)</label>
                            <input type="password" name="install_key" id="install_key" class="form-control" autocomplete="off">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Run Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
