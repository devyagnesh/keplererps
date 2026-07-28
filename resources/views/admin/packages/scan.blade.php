@extends('admin.layouts.app')
@section('title', 'Gate Scan')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Gate Scan</h1>
        <x-admin.module-intro />
    </div>
    <a href="{{ route('admin.packages.index') }}" class="btn btn-light btn-sm">All Packages</a>
</div>

<div class="row">
<div class="col-xl-5">
    <div class="card custom-card">
        <div class="card-header"><div class="card-title">Scan Package</div></div>
        <div class="card-body">
            <form id="scanForm" action="{{ route('admin.packages.scan') }}" method="POST" novalidate autocomplete="off">
                @csrf
                <label class="form-label">Label number or QR payload *</label>
                <input type="text" class="form-control form-control-lg text-uppercase" name="code" id="scanCode" placeholder="PKG-00001" required autofocus>
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" name="confirm" value="1" id="scanConfirm" checked>
                    <label class="form-check-label" for="scanConfirm">Mark package verified on scan</label>
                </div>
                <button type="submit" class="btn btn-primary mt-3 w-100">Scan</button>
            </form>
            <p class="text-muted fs-12 mt-3 mb-0">Keyboard-wedge scanners submit automatically on the trailing enter key.</p>
        </div>
    </div>
</div>

<div class="col-xl-7">
    <div class="card custom-card">
        <div class="card-header"><div class="card-title">Last Scan</div></div>
        <div class="card-body">
            <div id="scanResult" class="text-muted">Nothing scanned yet.</div>
        </div>
    </div>
    <div class="card custom-card">
        <div class="card-header"><div class="card-title">Scan History (this session)</div></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered text-nowrap w-100">
                <thead><tr><th>Time</th><th>Label</th><th>Item</th><th>Qty</th><th>Status</th></tr></thead>
                <tbody id="scanHistory"><tr id="scanHistoryEmpty"><td colspan="5" class="text-muted">No scans yet.</td></tr></tbody>
            </table>
        </div>
    </div>
</div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/packages/scan.js') }}"></script>
@endpush
