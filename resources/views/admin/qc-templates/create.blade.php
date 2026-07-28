@extends('admin.layouts.app')
@section('title', 'Create QC Template')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Create QC Template</h1>
        <x-admin.module-intro />
    </div>
    <a href="{{ route('admin.qc-templates.index') }}" class="btn btn-light btn-sm">Back</a>
</div>
@include('admin.qc-templates._form', [
    'action' => route('admin.qc-templates.store'),
    'method' => 'POST',
    'template' => null,
])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/qc-templates/form.js') }}"></script>
@endpush
