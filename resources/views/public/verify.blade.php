<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify {{ $token }}</title>
    <link rel="stylesheet" href="{{ asset('assets/admin/css/styles.css') }}">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:640px">
    <h1 class="h4 mb-4">Kepler verification</h1>
    @if($payload === null)
        <div class="alert alert-danger">No record found for this code.</div>
    @else
        <div class="card shadow-sm"><div class="card-body">
            <p class="text-muted text-uppercase small mb-2">{{ str_replace('_', ' ', $payload['type']) }}</p>
            <dl class="row mb-0">
                @foreach($payload['data'] as $key => $value)
                    <dt class="col-sm-4 text-muted">{{ str_replace('_', ' ', $key) }}</dt>
                    <dd class="col-sm-8">{{ $value ?? '—' }}</dd>
                @endforeach
            </dl>
        </div></div>
    @endif
</div>
</body>
</html>
