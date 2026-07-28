@extends('admin.layouts.app')
@section('title', 'Create Production Entry')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Create Production Entry</h1>
        <x-admin.module-intro />
    </div>
    <a href="{{ route('admin.production-entries.index') }}" class="btn btn-light btn-sm">Back</a>
</div>
@include('admin.production-entries._form', [
    'action' => route('admin.production-entries.store'),
    'method' => 'POST',
    'productionEntry' => null,
    'selectedWorkOrderId' => $selectedWorkOrderId ?? null,
    'workOrder' => $workOrder ?? null,
])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/production-entries/form.js') }}"></script>
@endpush
