@extends('admin.layouts.app')
@section('title', 'Raise QC Inspection')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Raise QC Inspection</h1>
        <x-admin.module-intro />
        <div class="text-muted fs-12">In-process, final, pre-dispatch and customer-return stages.</div>
    </div>
    <a href="{{ route('admin.qc-inspections.index') }}" class="btn btn-light btn-sm">Back</a>
</div>

<div class="card custom-card"><div class="card-body">
<form id="inspectionCreateForm" action="{{ route('admin.qc-inspections.store') }}" method="POST" novalidate>
@csrf
<div class="row gy-3">
    <div class="col-md-3">
        <label class="form-label">Date *</label>
        <input type="date" class="form-control" name="document_date" value="{{ old('document_date', now()->toDateString()) }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Stage *</label>
        <select name="inspection_type" class="form-select" required>
            <option value="">Select</option>
            @foreach ($types as $type)
                <option value="{{ $type->value }}" @selected(old('inspection_type') === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Item *</label>
        <select name="item_id" id="inspectionItem" class="form-select select2" required>
            <option value="">Select</option>
            @foreach ($items as $item)
                <option value="{{ $item->id }}" @selected((string) old('item_id') === (string) $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Batch</label>
        <select name="batch_id" id="inspectionBatch" class="form-select">
            <option value="">None</option>
            @foreach ($batches as $batch)
                <option value="{{ $batch->id }}" data-item="{{ $batch->item_id }}" @selected((string) old('batch_id') === (string) $batch->id)>{{ $batch->batch_no }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Lot quantity *</label>
        <input type="number" step="0.0001" min="0.0001" class="form-control" name="lot_quantity" value="{{ old('lot_quantity') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Sample size</label>
        <input type="number" step="0.0001" min="0.0001" class="form-control" name="sample_size" value="{{ old('sample_size') }}" placeholder="Auto from template">
    </div>
    <div class="col-md-3">
        <label class="form-label">Sample override reason</label>
        <input type="text" class="form-control" name="sample_override_reason" value="{{ old('sample_override_reason') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Hold (quarantine) warehouse</label>
        <select name="quarantine_warehouse_id" class="form-select select2">
            <option value="">No stock hold</option>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected((string) old('quarantine_warehouse_id') === (string) $warehouse->id)>{{ $warehouse->code }} — {{ $warehouse->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Target store warehouse</label>
        <select name="target_warehouse_id" class="form-select select2">
            <option value="">None</option>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected((string) old('target_warehouse_id') === (string) $warehouse->id)>{{ $warehouse->code }} — {{ $warehouse->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Work order</label>
        <select name="work_order_id" class="form-select select2">
            <option value="">None</option>
            @foreach ($workOrders as $workOrder)
                <option value="{{ $workOrder->id }}" @selected((string) old('work_order_id') === (string) $workOrder->id)>{{ $workOrder->document_no }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Sales order</label>
        <select name="sales_order_id" class="form-select select2">
            <option value="">None</option>
            @foreach ($salesOrders as $salesOrder)
                <option value="{{ $salesOrder->id }}" @selected((string) old('sales_order_id') === (string) $salesOrder->id)>{{ $salesOrder->document_no }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="form-label">Remarks</label>
        <textarea class="form-control" name="remarks" rows="2">{{ old('remarks') }}</textarea>
    </div>
</div>
<div class="mt-3">
    <button class="btn btn-primary" type="submit">Raise inspection</button>
    <a href="{{ route('admin.qc-inspections.index') }}" class="btn btn-light">Cancel</a>
</div>
</form>
</div></div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/qc-inspections/create.js') }}"></script>
@endpush
