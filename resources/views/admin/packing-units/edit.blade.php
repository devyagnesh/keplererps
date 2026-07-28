@extends('admin.layouts.app')
@section('title', 'Edit Packing Unit')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">{{ $unit->code }}</h1>
        <x-admin.module-intro />
        <p class="text-muted mb-0">{{ $unit->nestingPath() }} · {{ number_format($unit->baseQuantity(), 4) }} {{ $unit->uom?->code }}</p>
    </div>
    <a href="{{ route('admin.packing-units.index') }}" class="btn btn-light btn-sm">Back</a>
</div>
@include('admin.packing-units._form', ['action' => route('admin.packing-units.update', $unit), 'method' => 'PUT'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/packing-units/form.js') }}"></script>
@endpush
