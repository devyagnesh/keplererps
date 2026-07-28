@extends('admin.layouts.app')
@section('title', 'Packing Units')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Packing Units</h1>
        <x-admin.module-intro />
    </div>
    @can('packing_unit.create')
    <a href="{{ route('admin.packing-units.create') }}" class="btn btn-primary btn-sm">Add Packing Unit</a>
    @endcan
</div>
<div class="card custom-card"><div class="card-body">
<div class="table-responsive">
<table id="masterTable" class="table table-bordered text-nowrap w-100">
<thead><tr><th>ID</th><th>Code</th><th>Nesting</th><th>Item</th><th>Qty / Unit</th><th>Base Contents</th><th>Status</th><th>Action</th></tr></thead>
</table>
</div>
<p class="text-muted fs-12 mb-0">Base contents multiply every nesting level, so a carton of 5 boxes of 50 pieces resolves to 250 pieces.</p>
</div></div>
@endsection
@push('scripts')
<script>window.masterDataUrl = @json(route('admin.packing-units.data'));</script>
<script src="{{ asset('assets/admin/js/admin/packing-units/list.js') }}"></script>
@endpush
