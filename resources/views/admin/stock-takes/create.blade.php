@extends('admin.layouts.app')
@section('title', 'New Stock Take')
@section('content')
<div class="my-4 page-header-breadcrumb"><h1 class="page-title fw-semibold fs-18 mb-0">New Stock Take</h1></div>
<div class="card custom-card"><div class="card-body">
<form id="stockTakeCreateForm" data-ajax="1" method="post" action="{{ route('admin.stock-takes.store') }}">
    @csrf
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Warehouse</label>
            <select name="warehouse_id" class="form-select" required>
                <option value="">Select</option>
                @foreach($warehouses as $wh)
                    <option value="{{ $wh->id }}">{{ $wh->code }} — {{ $wh->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3"><label class="form-label">Date</label><input type="date" name="document_date" class="form-control" value="{{ now()->toDateString() }}"></div>
        <div class="col-md-5"><label class="form-label">Remarks</label><input type="text" name="remarks" class="form-control"></div>
        <div class="col-md-4 form-check mt-4"><input type="checkbox" class="form-check-input" name="seed" value="1" id="seedBalances" checked><label for="seedBalances" class="form-check-label">Seed from balances</label></div>
    </div>
    <button type="submit" class="btn btn-primary mt-3">Create</button>
</form>
</div></div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/gap-close/forms.js') }}"></script>
@endpush
