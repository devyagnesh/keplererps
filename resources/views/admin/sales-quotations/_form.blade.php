@php
    $isEditable = ! $salesQuotation || $salesQuotation->status->isEditable();
    $lines = old('items', $salesQuotation?->items?->map(fn ($l) => [
        'item_id' => $l->item_id,
        'uom_id' => $l->uom_id,
        'quantity' => $l->quantity,
        'rate' => $l->rate,
        'discount_percent' => $l->discount_percent,
        'gst_rate' => $l->gst_rate,
    ])->toArray() ?? [['item_id' => '', 'uom_id' => '', 'quantity' => '', 'rate' => '', 'discount_percent' => 0, 'gst_rate' => '']]);
@endphp
<div class="card custom-card"><div class="card-body">
<form id="salesQuotationForm" action="{{ $action }}" method="POST" novalidate @can('stock_balance.view') data-availability-url="{{ route('admin.stock-balances.availability') }}" @endcan>
@csrf
<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3 mb-3">
    <div class="col-md-3"><label class="form-label">Date *</label><input type="date" class="form-control" name="document_date" value="{{ old('document_date', optional($salesQuotation?->document_date)->format('Y-m-d') ?? now()->toDateString()) }}" {{ $isEditable ? '' : 'readonly' }} required></div>
    <div class="col-md-3"><label class="form-label">Valid Until *</label><input type="date" class="form-control" name="valid_until" value="{{ old('valid_until', optional($salesQuotation?->valid_until)->format('Y-m-d') ?? now()->addDays(30)->toDateString()) }}" {{ $isEditable ? '' : 'readonly' }} required></div>
    <div class="col-md-6"><label class="form-label">Customer *</label>
        <select name="customer_id" class="form-select select2" {{ $isEditable ? '' : 'disabled' }} required>
            <option value="">Select</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" @selected((string) old('customer_id', $salesQuotation?->customer_id) === (string) $customer->id)>{{ $customer->party_code }} — {{ $customer->party_name }}</option>
            @endforeach
        </select>
        @unless ($isEditable)<input type="hidden" name="customer_id" value="{{ $salesQuotation->customer_id }}">@endunless
    </div>
    <div class="col-md-4"><label class="form-label">Warehouse *</label>
        <select name="warehouse_id" class="form-select select2" {{ $isEditable ? '' : 'disabled' }} required>
            <option value="">Select</option>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id', $salesQuotation?->warehouse_id) === (string) $warehouse->id)>{{ $warehouse->code }} — {{ $warehouse->name }}</option>
            @endforeach
        </select>
        @unless ($isEditable)<input type="hidden" name="warehouse_id" value="{{ $salesQuotation->warehouse_id }}">@endunless
    </div>
    <div class="col-md-4"><label class="form-label">Place of Supply *</label>
        <select name="place_of_supply_state_id" class="form-select select2" {{ $isEditable ? '' : 'disabled' }} required>
            <option value="">Select state</option>
            @foreach ($states as $state)
                <option value="{{ $state->id }}" @selected((string) old('place_of_supply_state_id', $salesQuotation?->place_of_supply_state_id) === (string) $state->id)>{{ $state->code }} — {{ $state->name }}</option>
            @endforeach
        </select>
        @unless ($isEditable)<input type="hidden" name="place_of_supply_state_id" value="{{ $salesQuotation->place_of_supply_state_id }}">@endunless
    </div>
    <div class="col-md-4"><label class="form-label">Remarks</label><input type="text" class="form-control" name="remarks" value="{{ old('remarks', $salesQuotation?->remarks) }}" {{ $isEditable ? '' : 'readonly' }}></div>
</div>
<div class="d-flex justify-content-between align-items-center mb-2"><h6 class="mb-0">Lines</h6>@if ($isEditable)<button type="button" class="btn btn-sm btn-outline-primary" id="btnAddLine">Add line</button>@endif</div>
<div id="lineRows">
@foreach ($lines as $index => $line)
<div class="row g-2 mb-2 line-row">
    <div class="col-md-3"><select name="items[{{ $index }}][item_id]" class="form-select sq-item" {{ $isEditable ? '' : 'disabled' }} required><option value="">Item</option>@foreach ($items as $item)<option value="{{ $item->id }}" data-uom="{{ $item->stock_uom_id }}" data-rate="{{ $item->selling_price }}" data-gst="{{ $item->gst_rate }}" @selected((string) ($line['item_id'] ?? '') === (string) $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>@endforeach</select><div class="fs-11 text-muted mt-1 sq-atp"></div></div>
    <div class="col-md-2"><select name="items[{{ $index }}][uom_id]" class="form-select sq-uom" {{ $isEditable ? '' : 'disabled' }} required><option value="">UOM</option>@foreach ($uoms as $uom)<option value="{{ $uom->id }}" @selected((string) ($line['uom_id'] ?? '') === (string) $uom->id)>{{ $uom->code }}</option>@endforeach</select></div>
    <div class="col-md-2"><input type="number" step="0.0001" min="0.0001" class="form-control" name="items[{{ $index }}][quantity]" value="{{ $line['quantity'] ?? '' }}" placeholder="Qty" {{ $isEditable ? '' : 'readonly' }} required></div>
    <div class="col-md-1"><input type="number" step="0.0001" min="0" class="form-control" name="items[{{ $index }}][rate]" value="{{ $line['rate'] ?? '' }}" placeholder="Rate" {{ $isEditable ? '' : 'readonly' }} required></div>
    <div class="col-md-1"><input type="number" step="0.01" min="0" max="100" class="form-control" name="items[{{ $index }}][discount_percent]" value="{{ $line['discount_percent'] ?? 0 }}" placeholder="Disc %" {{ $isEditable ? '' : 'readonly' }}></div>
    <div class="col-md-1"><input type="number" step="0.01" min="0" class="form-control" name="items[{{ $index }}][gst_rate]" value="{{ $line['gst_rate'] ?? '' }}" placeholder="GST" {{ $isEditable ? '' : 'readonly' }}></div>
    <div class="col-md-1">@if ($isEditable)<button type="button" class="btn btn-danger-light btn-remove-line"><i class="ri-close-line"></i></button>@endif</div>
</div>
@endforeach
</div>
@if ($isEditable)
<div class="mt-3"><button class="btn btn-primary" type="submit">Save</button><a href="{{ route('admin.sales-quotations.index') }}" class="btn btn-light">Cancel</a></div>
@endif
</form>
</div></div>
<template id="tplLine">
<div class="row g-2 mb-2 line-row">
    <div class="col-md-3"><select name="items[__INDEX__][item_id]" class="form-select sq-item" required><option value="">Item</option>@foreach ($items as $item)<option value="{{ $item->id }}" data-uom="{{ $item->stock_uom_id }}" data-rate="{{ $item->selling_price }}" data-gst="{{ $item->gst_rate }}">{{ $item->item_code }} — {{ $item->item_name }}</option>@endforeach</select><div class="fs-11 text-muted mt-1 sq-atp"></div></div>
    <div class="col-md-2"><select name="items[__INDEX__][uom_id]" class="form-select sq-uom" required><option value="">UOM</option>@foreach ($uoms as $uom)<option value="{{ $uom->id }}">{{ $uom->code }}</option>@endforeach</select></div>
    <div class="col-md-2"><input type="number" step="0.0001" min="0.0001" class="form-control" name="items[__INDEX__][quantity]" placeholder="Qty" required></div>
    <div class="col-md-1"><input type="number" step="0.0001" min="0" class="form-control" name="items[__INDEX__][rate]" placeholder="Rate" required></div>
    <div class="col-md-1"><input type="number" step="0.01" min="0" max="100" class="form-control" name="items[__INDEX__][discount_percent]" value="0" placeholder="Disc %"></div>
    <div class="col-md-1"><input type="number" step="0.01" min="0" class="form-control" name="items[__INDEX__][gst_rate]" placeholder="GST"></div>
    <div class="col-md-1"><button type="button" class="btn btn-danger-light btn-remove-line"><i class="ri-close-line"></i></button></div>
</div>
</template>
