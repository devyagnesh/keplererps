@php
    $isDraft = ! $salesOrder || $salesOrder->status->value === 'draft';
    $lines = old('items', $salesOrder?->items?->map(fn ($l) => [
        'item_id' => $l->item_id,
        'uom_id' => $l->uom_id,
        'quantity' => $l->quantity,
        'rate' => $l->rate,
        'discount_percent' => $l->discount_percent,
        'gst_rate' => $l->gst_rate,
    ])->toArray() ?? [['item_id' => '', 'uom_id' => '', 'quantity' => '', 'rate' => '', 'discount_percent' => 0, 'gst_rate' => '']]);
@endphp
<div class="card custom-card"><div class="card-body">
<form id="salesOrderForm" action="{{ $action }}" method="POST" novalidate @can('stock_balance.view') data-availability-url="{{ route('admin.stock-balances.availability') }}" @endcan>
@csrf
<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3 mb-3">
    <div class="col-md-3"><label class="form-label">Date *</label><input type="date" class="form-control" name="document_date" value="{{ old('document_date', optional($salesOrder?->document_date)->format('Y-m-d') ?? now()->toDateString()) }}" {{ $isDraft ? '' : 'readonly' }} required></div>
    <div class="col-md-5"><label class="form-label">Customer *</label>
        <select name="customer_id" class="form-select select2" {{ $isDraft ? '' : 'disabled' }} required>
            <option value="">Select</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" @selected((string) old('customer_id', $salesOrder?->customer_id) === (string) $customer->id)>{{ $customer->party_code }} — {{ $customer->party_name }}</option>
            @endforeach
        </select>
        @unless ($isDraft)<input type="hidden" name="customer_id" value="{{ $salesOrder->customer_id }}">@endunless
    </div>
    <div class="col-md-4"><label class="form-label">Warehouse *</label>
        <select name="warehouse_id" class="form-select select2" {{ $isDraft ? '' : 'disabled' }} required>
            <option value="">Select</option>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id', $salesOrder?->warehouse_id) === (string) $warehouse->id)>{{ $warehouse->code }} — {{ $warehouse->name }}</option>
            @endforeach
        </select>
        @unless ($isDraft)<input type="hidden" name="warehouse_id" value="{{ $salesOrder->warehouse_id }}">@endunless
    </div>
    <div class="col-md-4"><label class="form-label">Place of Supply *</label>
        <select name="place_of_supply_state_id" class="form-select select2" {{ $isDraft ? '' : 'disabled' }} required>
            <option value="">Select state</option>
            @foreach ($states as $state)
                <option value="{{ $state->id }}" @selected((string) old('place_of_supply_state_id', $salesOrder?->place_of_supply_state_id) === (string) $state->id)>{{ $state->code }} — {{ $state->name }}</option>
            @endforeach
        </select>
        @unless ($isDraft)<input type="hidden" name="place_of_supply_state_id" value="{{ $salesOrder->place_of_supply_state_id }}">@endunless
    </div>
    <div class="col-md-2"><label class="form-label">Customer PO No</label><input type="text" class="form-control" name="customer_po_no" value="{{ old('customer_po_no', $salesOrder?->customer_po_no) }}" {{ $isDraft ? '' : 'readonly' }}></div>
    <div class="col-md-2"><label class="form-label">Customer PO Date</label><input type="date" class="form-control" name="customer_po_date" value="{{ old('customer_po_date', optional($salesOrder?->customer_po_date)->format('Y-m-d')) }}" {{ $isDraft ? '' : 'readonly' }}></div>
    <div class="col-md-2"><label class="form-label">Expected Delivery *</label><input type="date" class="form-control" name="expected_delivery_date" value="{{ old('expected_delivery_date', optional($salesOrder?->expected_delivery_date)->format('Y-m-d') ?? now()->addDays(7)->toDateString()) }}" {{ $isDraft ? '' : 'readonly' }} required></div>
    <div class="col-md-6"><label class="form-label">Remarks</label><input type="text" class="form-control" name="remarks" value="{{ old('remarks', $salesOrder?->remarks) }}" {{ $isDraft ? '' : 'readonly' }}></div>
</div>
<div class="d-flex justify-content-between align-items-center mb-2"><h6 class="mb-0">Lines</h6>@if ($isDraft)<button type="button" class="btn btn-sm btn-outline-primary" id="btnAddLine">Add line</button>@endif</div>
<div id="lineRows">
@foreach ($lines as $index => $line)
<div class="row g-2 mb-2 line-row">
    <div class="col-md-3"><select name="items[{{ $index }}][item_id]" class="form-select so-item" {{ $isDraft ? '' : 'disabled' }} required><option value="">Item</option>@foreach ($items as $item)<option value="{{ $item->id }}" data-uom="{{ $item->stock_uom_id }}" data-rate="{{ $item->selling_price }}" data-gst="{{ $item->gst_rate }}" @selected((string) ($line['item_id'] ?? '') === (string) $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>@endforeach</select><div class="fs-11 text-muted mt-1 so-atp"></div></div>
    <div class="col-md-2"><select name="items[{{ $index }}][uom_id]" class="form-select so-uom" {{ $isDraft ? '' : 'disabled' }} required><option value="">UOM</option>@foreach ($uoms as $uom)<option value="{{ $uom->id }}" @selected((string) ($line['uom_id'] ?? '') === (string) $uom->id)>{{ $uom->code }}</option>@endforeach</select></div>
    <div class="col-md-2"><input type="number" step="0.0001" min="0.0001" class="form-control" name="items[{{ $index }}][quantity]" value="{{ $line['quantity'] ?? '' }}" placeholder="Qty" {{ $isDraft ? '' : 'readonly' }} required></div>
    <div class="col-md-1"><input type="number" step="0.0001" min="0" class="form-control" name="items[{{ $index }}][rate]" value="{{ $line['rate'] ?? '' }}" placeholder="Rate" {{ $isDraft ? '' : 'readonly' }} required></div>
    <div class="col-md-1"><input type="number" step="0.01" min="0" max="100" class="form-control" name="items[{{ $index }}][discount_percent]" value="{{ $line['discount_percent'] ?? 0 }}" placeholder="Disc %" {{ $isDraft ? '' : 'readonly' }}></div>
    <div class="col-md-1"><input type="number" step="0.01" min="0" class="form-control" name="items[{{ $index }}][gst_rate]" value="{{ $line['gst_rate'] ?? '' }}" placeholder="GST" {{ $isDraft ? '' : 'readonly' }}></div>
    <div class="col-md-1">@if ($isDraft)<button type="button" class="btn btn-danger-light btn-remove-line"><i class="ri-close-line"></i></button>@endif</div>
</div>
@endforeach
</div>
@if ($isDraft)
<div class="mt-3"><button class="btn btn-primary" type="submit">Save Draft</button><a href="{{ route('admin.sales-orders.index') }}" class="btn btn-light">Cancel</a></div>
@endif
</form>
</div></div>
<template id="tplLine">
<div class="row g-2 mb-2 line-row">
    <div class="col-md-3"><select name="items[__INDEX__][item_id]" class="form-select so-item" required><option value="">Item</option>@foreach ($items as $item)<option value="{{ $item->id }}" data-uom="{{ $item->stock_uom_id }}" data-rate="{{ $item->selling_price }}" data-gst="{{ $item->gst_rate }}">{{ $item->item_code }} — {{ $item->item_name }}</option>@endforeach</select><div class="fs-11 text-muted mt-1 so-atp"></div></div>
    <div class="col-md-2"><select name="items[__INDEX__][uom_id]" class="form-select so-uom" required><option value="">UOM</option>@foreach ($uoms as $uom)<option value="{{ $uom->id }}">{{ $uom->code }}</option>@endforeach</select></div>
    <div class="col-md-2"><input type="number" step="0.0001" min="0.0001" class="form-control" name="items[__INDEX__][quantity]" placeholder="Qty" required></div>
    <div class="col-md-1"><input type="number" step="0.0001" min="0" class="form-control" name="items[__INDEX__][rate]" placeholder="Rate" required></div>
    <div class="col-md-1"><input type="number" step="0.01" min="0" max="100" class="form-control" name="items[__INDEX__][discount_percent]" value="0" placeholder="Disc %"></div>
    <div class="col-md-1"><input type="number" step="0.01" min="0" class="form-control" name="items[__INDEX__][gst_rate]" placeholder="GST"></div>
    <div class="col-md-1"><button type="button" class="btn btn-danger-light btn-remove-line"><i class="ri-close-line"></i></button></div>
</div>
</template>
