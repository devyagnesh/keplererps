@extends('admin.layouts.app')
@section('title', 'Bill of Materials')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Bill of Materials</h1>
    @can('bom.create')
    <a href="{{ route('admin.boms.create') }}" class="btn btn-primary btn-sm">Add BOM</a>
    @endcan
</div>
<div class="card custom-card"><div class="card-body">
    <div class="row mb-3">
        <div class="col-md-4">
            <select id="filterItem" class="form-select select2">
                <option value="">All finished items</option>
                @foreach ($items as $item)
                    <option value="{{ $item->id }}">{{ $item->item_code }} — {{ $item->item_name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="table-responsive">
        <table id="masterTable" class="table table-bordered text-nowrap w-100">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>BOM No</th>
                    <th>Item</th>
                    <th>Version</th>
                    <th>Valid From</th>
                    <th>Status</th>
                    <th>Std Cost</th>
                    <th>Action</th>
                </tr>
            </thead>
        </table>
    </div>
</div></div>
@endsection
@push('scripts')
<script>window.masterDataUrl = @json(route('admin.boms.data'));</script>
<script src="{{ asset('assets/admin/js/admin/boms/list.js') }}"></script>
@endpush
