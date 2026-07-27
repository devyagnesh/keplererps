@php
    $isDraft = ! $purchaseOrder || $purchaseOrder->status->value === 'draft';
    $lines = old('items', $purchaseOrder?->items?->map(fn ($l) => [
        'item_id' => $l->item_id,
        'uom_id' => $l->uom_id,
        'quantity' => $l->quantity,
        'rate' => $l->rate,
        'gst_rate' => $l->gst_rate,
        'tolerance_percent' => $l->tolerance_percent,
    ])->toArray() ?? [['item_id' => '', 'uom_id' => '', 'quantity' => '', 'rate' => '', 'gst_rate' => '', 'tolerance_percent' => 0]]);
@endphp
<div class="card custom-card"><div class="card-body">
<form id="purchaseOrderForm" action="{{ $action }}" method="POST" novalidate>
@csrf
<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3 mb-3">
    <div class="col-md-3"><label class="form-label">Date *</label><input type="date" class="form-control" name="document_date" value="{{ old('document_date', optional($purchaseOrder?->document_date)->format('Y-m-d') ?? now()->toDateString()) }}" {{ $isDraft ? '' : 'readonly' }} required></div>
    <div class="col-md-4"><label class="form-label">Supplier *</label>
        <select name="supplier_id" class="form-select select2" {{ $isDraft ? '' : 'disabled' }} required>
            <option value="">Select</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $purchaseOrder?->supplier_id) === (string) $supplier->id)>{{ $supplier->party_code }} — {{ $supplier->party_name }}</option>
            @endforeach
        </select>
        @unless ($isDraft)<input type="hidden" name="supplier_id" value="{{ $purchaseOrder->supplier_id }}">@endunless
    </div>
    <div class="col-md-5"><label class="form-label">Delivery Warehouse *</label>
        <select name="warehouse_id" class="form-select select2" {{ $isDraft ? '' : 'disabled' }} required>
            <option value="">Select</option>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id', $purchaseOrder?->warehouse_id) === (string) $warehouse->id)>{{ $warehouse->code }} — {{ $warehouse->name }}</option>
            @endforeach
        </select>
        @unless ($isDraft)<input type="hidden" name="warehouse_id" value="{{ $purchaseOrder->warehouse_id }}">@endunless
    </div>
    <div class="col-md-3"><label class="form-label">Expected Delivery *</label><input type="date" class="form-control" name="expected_delivery_date" value="{{ old('expected_delivery_date', optional($purchaseOrder?->expected_delivery_date)->format('Y-m-d') ?? now()->addDays(7)->toDateString()) }}" {{ $isDraft ? '' : 'readonly' }} required></div>
    <div class="col-md-9"><label class="form-label">Remarks</label><input type="text" class="form-control" name="remarks" value="{{ old('remarks', $purchaseOrder?->remarks) }}" {{ $isDraft ? '' : 'readonly' }}></div>
</div>
<div class="d-flex justify-content-between align-items-center mb-2"><h6 class="mb-0">Lines</h6>@if ($isDraft)<button type="button" class="btn btn-sm btn-outline-primary" id="btnAddLine">Add line</button>@endif</div>
<div id="lineRows">
@foreach ($lines as $index => $line)
<div class="row g-2 mb-2 line-row">
    <div class="col-md-3"><select name="items[{{ $index }}][item_id]" class="form-select po-item" {{ $isDraft ? '' : 'disabled' }} required><option value="">Item</option>@foreach ($items as $item)<option value="{{ $item->id }}" data-uom="{{ $item->stock_uom_id }}" data-rate="{{ $item->standard_cost }}" data-gst="{{ $item->gst_rate }}" @selected((string) ($line['item_id'] ?? '') === (string) $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>@endforeach</select></div>
    <div class="col-md-2"><select name="items[{{ $index }}][uom_id]" class="form-select po-uom" {{ $isDraft ? '' : 'disabled' }} required><option value="">UOM</option>@foreach ($uoms as $uom)<option value="{{ $uom->id }}" @selected((string) ($line['uom_id'] ?? '') === (string) $uom->id)>{{ $uom->code }}</option>@endforeach</select></div>
    <div class="col-md-2"><input type="number" step="0.0001" min="0.0001" class="form-control" name="items[{{ $index }}][quantity]" value="{{ $line['quantity'] ?? '' }}" {{ $isDraft ? '' : 'readonly' }} required></div>
    <div class="col-md-1"><input type="number" step="0.0001" min="0" class="form-control" name="items[{{ $index }}][rate]" value="{{ $line['rate'] ?? '' }}" {{ $isDraft ? '' : 'readonly' }} required></div>
    <div class="col-md-1"><input type="number" step="0.01" min="0" class="form-control" name="items[{{ $index }}][gst_rate]" value="{{ $line['gst_rate'] ?? '' }}" {{ $isDraft ? '' : 'readonly' }}></div>
    <div class="col-md-1"><input type="number" step="0.01" min="0" max="20" class="form-control" name="items[{{ $index }}][tolerance_percent]" value="{{ $line['tolerance_percent'] ?? 0 }}" {{ $isDraft ? '' : 'readonly' }}></div>
    <div class="col-md-1">@if ($isDraft)<button type="button" class="btn btn-danger-light btn-remove-line"><i class="ri-close-line"></i></button>@endif</div>
</div>
@endforeach
</div>
@if ($isDraft)
<div class="mt-3"><button class="btn btn-primary" type="submit">Save Draft</button><a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-light">Cancel</a></div>
@endif
</form>
</div></div>
<template id="tplLine">
<div class="row g-2 mb-2 line-row">
    <div class="col-md-3"><select name="items[__INDEX__][item_id]" class="form-select po-item" required><option value="">Item</option>@foreach ($items as $item)<option value="{{ $item->id }}" data-uom="{{ $item->stock_uom_id }}" data-rate="{{ $item->standard_cost }}" data-gst="{{ $item->gst_rate }}">{{ $item->item_code }} — {{ $item->item_name }}</option>@endforeach</select></div>
    <div class="col-md-2"><select name="items[__INDEX__][uom_id]" class="form-select po-uom" required><option value="">UOM</option>@foreach ($uoms as $uom)<option value="{{ $uom->id }}">{{ $uom->code }}</option>@endforeach</select></div>
    <div class="col-md-2"><input type="number" step="0.0001" min="0.0001" class="form-control" name="items[__INDEX__][quantity]" required></div>
    <div class="col-md-1"><input type="number" step="0.0001" min="0" class="form-control" name="items[__INDEX__][rate]" required></div>
    <div class="col-md-1"><input type="number" step="0.01" min="0" class="form-control" name="items[__INDEX__][gst_rate]"></div>
    <div class="col-md-1"><input type="number" step="0.01" min="0" max="20" class="form-control" name="items[__INDEX__][tolerance_percent]" value="0"></div>
    <div class="col-md-1"><button type="button" class="btn btn-danger-light btn-remove-line"><i class="ri-close-line"></i></button></div>
</div>
</template>
