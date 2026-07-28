@extends('admin.layouts.app')
@section('title', 'Items')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Item Master</h1>
        <x-admin.module-intro />
    </div>
    <a href="{{ route('admin.items.create') }}" class="btn btn-primary btn-sm">Add Item</a>
</div>
<div class="card custom-card">
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="form-label">Item Type</label>
                <select id="filterItemType" class="form-select">
                    <option value="">All</option>
                    @foreach ($itemTypes as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Category</label>
                <select id="filterCategory" class="form-select">
                    <option value="">All</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table id="itemTable" class="table table-bordered text-nowrap w-100">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>UOM</th>
                        <th>HSN</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
    window.itemDataUrl = @json(route('admin.items.data'));
</script>
<script src="{{ asset('assets/admin/js/admin/item/list.js') }}"></script>
@endpush
