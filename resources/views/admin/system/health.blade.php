@extends('admin.layouts.app')
@section('title', 'System Health')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">System Health</h1>
        <x-admin.module-intro />
    </div>
    <button type="button" class="btn btn-primary btn-sm" id="btnClearCache" data-url="{{ route('admin.system.clear-cache') }}">Clear Caches</button>
</div>
<div class="row">
@foreach ($checks as $check)
    @php
        $badge = match ($check['status']) {
            'ok' => 'bg-success-transparent',
            'warn' => 'bg-warning-transparent',
            default => 'bg-danger-transparent',
        };
    @endphp
    <div class="col-md-4 mb-3">
        <div class="card custom-card h-100"><div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <h6 class="mb-2">{{ $check['label'] }}</h6>
                <span class="badge {{ $badge }}">{{ strtoupper($check['status']) }}</span>
            </div>
            <p class="mb-0 text-muted">{{ $check['detail'] }}</p>
        </div></div>
    </div>
@endforeach
</div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/system/health.js') }}"></script>
@endpush
