@extends('admin.layouts.app')
@section('title', 'Edit BOM')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Edit BOM {{ $bom->bom_number }}</h1>
        <p class="text-muted mb-0">Version v{{ $bom->version }} · Std cost {{ number_format((float) $bom->rolled_total_cost, 2) }}</p>
    </div>
    <div class="d-flex gap-2">
        @can('bom.create')
        <button type="button" class="btn btn-outline-primary btn-sm" id="btnNewVersion" data-url="{{ route('admin.boms.new-version', $bom) }}">New Version</button>
        @endcan
        <a href="{{ route('admin.boms.index') }}" class="btn btn-light btn-sm">Back</a>
    </div>
</div>
@include('admin.boms._form', [
    'action' => route('admin.boms.update', $bom),
    'method' => 'PUT',
    'bom' => $bom,
])
@endsection
@push('scripts')
<script>
window.bomExplodeUrl = @json(route('admin.boms.explode', $bom));
</script>
<script src="{{ asset('assets/admin/js/admin/boms/form.js') }}"></script>
@endpush
