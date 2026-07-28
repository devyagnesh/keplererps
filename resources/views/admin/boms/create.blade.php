@extends('admin.layouts.app')
@section('title', 'Create BOM')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Create BOM</h1>
        <x-admin.module-intro />
    </div>
    <a href="{{ route('admin.boms.index') }}" class="btn btn-light btn-sm">Back</a>
</div>
@include('admin.boms._form', [
    'action' => route('admin.boms.store'),
    'method' => 'POST',
    'bom' => null,
])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/boms/form.js') }}"></script>
@endpush
