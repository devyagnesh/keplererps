@extends('admin.layouts.app')
@section('title', 'Add Packing Unit')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Add Packing Unit</h1>
    <a href="{{ route('admin.packing-units.index') }}" class="btn btn-light btn-sm">Back</a>
</div>
@include('admin.packing-units._form', ['unit' => null, 'action' => route('admin.packing-units.store'), 'method' => 'POST'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/packing-units/form.js') }}"></script>
@endpush
