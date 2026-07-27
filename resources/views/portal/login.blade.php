<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customer Portal Login</title>
    <link rel="stylesheet" href="{{ asset('assets/admin/css/styles.css') }}">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:420px">
    <h1 class="h4 mb-3">Customer Portal</h1>
    <div class="card shadow-sm"><div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        <form method="post" action="{{ route('portal.login.submit') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button class="btn btn-primary w-100" type="submit">Sign in</button>
        </form>
    </div></div>
</div>
</body>
</html>
