@extends('admin.layouts.app')
@section('title', isset($priceList) ? 'Edit Price List' : 'New Price List')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">{{ isset($priceList) ? 'Edit Price List' : 'New Price List' }}</h1>
        <x-admin.module-intro />
    </div>
    <a href="{{ route('admin.price-lists.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
</div>
<div class="card custom-card"><div class="card-body">
<form id="priceListForm" data-ajax="1" method="post" action="{{ isset($priceList) ? route('admin.price-lists.update', $priceList) : route('admin.price-lists.store') }}">
    @csrf
    @if(isset($priceList)) @method('PUT') @endif
    <div class="row g-3 mb-3">
        <div class="col-md-3"><label class="form-label">Code</label><input name="code" class="form-control" value="{{ $priceList->code ?? '' }}" required></div>
        <div class="col-md-5"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ $priceList->name ?? '' }}" required></div>
        <div class="col-md-2"><label class="form-label">Valid From</label><input type="date" name="valid_from" class="form-control" value="{{ optional($priceList->valid_from ?? null)?->toDateString() }}"></div>
        <div class="col-md-2"><label class="form-label">Valid To</label><input type="date" name="valid_to" class="form-control" value="{{ optional($priceList->valid_to ?? null)?->toDateString() }}"></div>
        <div class="col-md-3 form-check mt-4"><input type="checkbox" class="form-check-input" name="is_default" value="1" @checked($priceList->is_default ?? false) id="isDefault"><label for="isDefault" class="form-check-label">Default list</label></div>
        <div class="col-md-3 form-check mt-4"><input type="checkbox" class="form-check-input" name="is_active" value="1" @checked($priceList->is_active ?? true) id="isActive"><label for="isActive" class="form-check-label">Active</label></div>
    </div>
    <h6 class="fw-semibold">Items</h6>
    <div class="table-responsive mb-3">
        <table class="table table-bordered" id="priceItems">
            <thead><tr><th>Item</th><th>Min Qty</th><th>Rate</th></tr></thead>
            <tbody>
            @php $rows = isset($priceList) ? $priceList->items : collect([[]]); @endphp
            @foreach($rows as $i => $row)
            <tr>
                <td>
                    <select name="items[{{ $i }}][item_id]" class="form-select">
                        <option value="">Select</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}" @selected(($row->item_id ?? null) == $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
                        @endforeach
                    </select>
                </td>
                <td><input type="number" step="0.0001" name="items[{{ $i }}][min_qty]" class="form-control" value="{{ $row->min_qty ?? 1 }}"></td>
                <td><input type="number" step="0.0001" name="items[{{ $i }}][rate]" class="form-control" value="{{ $row->rate ?? '' }}"></td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <button type="submit" class="btn btn-primary">Save</button>
</form>
</div></div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/gap-close/forms.js') }}"></script>
@endpush
