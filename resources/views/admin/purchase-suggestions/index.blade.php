@extends('admin.layouts.app')
@section('title', 'Purchase Suggestions')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Purchase Suggestions</h1>
        <x-admin.module-intro />
    </div>
</div>
<div class="card custom-card"><div class="card-body">
    <div class="row mb-3">
        <div class="col-md-4">
            <select id="filterWarehouse" class="form-select select2">
                <option value="">All warehouses</option>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->code }} — {{ $warehouse->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><button type="button" class="btn btn-primary" id="btnLoadSuggestions">Refresh</button></div>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered text-nowrap w-100" id="suggestionTable">
            <thead>
                <tr>
                    <th>Item</th><th>Warehouse</th><th>Source</th><th>Free</th><th>On Order</th><th>Reorder / Required</th><th>Suggested</th><th>Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div></div>
@endsection
@push('scripts')
<script>
window.purchaseSuggestionUrl = @json(route('admin.purchase-suggestions.data'));
window.purchaseOrderCreateUrl = @json(route('admin.purchase-orders.create'));
</script>
<script src="{{ asset('assets/admin/js/admin/purchase-suggestions/list.js') }}"></script>
@endpush
