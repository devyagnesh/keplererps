@extends('admin.layouts.app')
@section('title', 'Edit Asset')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Edit Asset — {{ $asset->code }}</h1>
    <a href="{{ route('admin.work-centres.index') }}" class="btn btn-light btn-sm">Back</a>
</div>
@include('admin.work-centres._form', [
    'action' => route('admin.work-centres.update', $asset),
    'method' => 'PUT',
    'asset' => $asset,
])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/work-centres/form.js') }}"></script>
@endpush
