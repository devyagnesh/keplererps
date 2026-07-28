@extends('admin.layouts.app')
@section('title', 'Period Lock')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Period Lock</h1>
        <x-admin.module-intro />
    </div>
</div>
<div class="card custom-card"><div class="card-body">
    @if($current)
        <div class="alert alert-warning">Currently locked through <strong>{{ $current->locked_to->format('d M Y') }}</strong>@if($current->reason) — {{ $current->reason }}@endif</div>
    @else
        <div class="alert alert-info">No active period lock.</div>
    @endif
    <form id="periodLockForm" data-ajax="1" data-reload="1" method="post" action="{{ route('admin.period-locks.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Lock through</label><input type="date" name="locked_to" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Reason</label><input type="text" name="reason" class="form-control" maxlength="255"></div>
            <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-danger w-100">Lock</button></div>
        </div>
    </form>
</div></div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/gap-close/forms.js') }}"></script>
@endpush
