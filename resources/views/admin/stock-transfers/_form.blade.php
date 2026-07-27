@php
    $isDraft = ! $stockTransfer || $stockTransfer->status->value === 'draft';
    $lines = old('items', $stockTransfer?->items?->map(fn ($l) => [
        'item_id' => $l->item_id, 'quantity' => $l->quantity, 'rate' => $l->rate,
    ])->toArray() ?? [['item_id' => '', 'quantity' => '', 'rate' => '']]);
@endphp
<div class="card custom-card"><div class="card-body">
<form id="inventoryDocForm" action="{{ $action }}" method="POST" novalidate>
@csrf<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3 mb-3">
    <div class="col-md-3"><label class="form-label">Date *</label><input type="date" class="form-control" name="document_date" value="{{ old('document_date', optional($stockTransfer?->document_date)->format('Y-m-d') ?? now()->toDateString()) }}" {{ $isDraft ? '' : 'readonly' }} required></div>
    <div class="col-md-4"><label class="form-label">From warehouse *</label>
        <select name="from_warehouse_id" class="form-select select2" {{ $isDraft ? '' : 'disabled' }} required>
            <option value="">Select</option>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected((string) old('from_warehouse_id', $stockTransfer?->from_warehouse_id) === (string) $warehouse->id)>{{ $warehouse->code }} — {{ $warehouse->name }}</option>
            @endforeach
        </select>
        @unless ($isDraft)<input type="hidden" name="from_warehouse_id" value="{{ $stockTransfer->from_warehouse_id }}">@endunless
    </div>
    <div class="col-md-4"><label class="form-label">To warehouse *</label>
        <select name="to_warehouse_id" class="form-select select2" {{ $isDraft ? '' : 'disabled' }} required>
            <option value="">Select</option>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected((string) old('to_warehouse_id', $stockTransfer?->to_warehouse_id) === (string) $warehouse->id)>{{ $warehouse->code }} — {{ $warehouse->name }}</option>
            @endforeach
        </select>
        @unless ($isDraft)<input type="hidden" name="to_warehouse_id" value="{{ $stockTransfer->to_warehouse_id }}">@endunless
    </div>
    <div class="col-md-12"><label class="form-label">Remarks</label><input type="text" class="form-control" name="remarks" value="{{ old('remarks', $stockTransfer?->remarks) }}" {{ $isDraft ? '' : 'readonly' }}></div>
</div>
<div class="d-flex justify-content-between align-items-center mb-2"><h6 class="mb-0">Lines</h6>@if ($isDraft)<button type="button" class="btn btn-sm btn-outline-primary" id="btnAddLine">Add line</button>@endif</div>
<div id="lineRows">
@foreach ($lines as $index => $line)
<div class="row g-2 mb-2 line-row">
    <div class="col-md-5"><select name="items[{{ $index }}][item_id]" class="form-select" {{ $isDraft ? '' : 'disabled' }} required><option value="">Item</option>@foreach ($items as $item)<option value="{{ $item->id }}" @selected((string) ($line['item_id'] ?? '') === (string) $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>@endforeach</select></div>
    <div class="col-md-3"><input type="number" step="0.0001" class="form-control" name="items[{{ $index }}][quantity]" value="{{ $line['quantity'] ?? '' }}" {{ $isDraft ? '' : 'readonly' }} required></div>
    <div class="col-md-2"><input type="number" step="0.0001" class="form-control" name="items[{{ $index }}][rate]" value="{{ $line['rate'] ?? '' }}" {{ $isDraft ? '' : 'readonly' }} placeholder="Rate (avg if blank)"></div>
    <div class="col-md-2">@if ($isDraft)<button type="button" class="btn btn-danger-light btn-remove-line"><i class="ri-close-line"></i></button>@endif</div>
</div>
@endforeach
</div>
@if ($isDraft)<div class="mt-3"><button class="btn btn-primary" type="submit">Save Draft</button><a href="{{ route('admin.stock-transfers.index') }}" class="btn btn-light">Cancel</a></div>
@else<div class="mt-3"><a href="{{ route('admin.stock-transfers.index') }}" class="btn btn-light">Back</a></div>@endif
</form></div></div>
<template id="tplLine"><div class="row g-2 mb-2 line-row">
<div class="col-md-5"><select name="items[__INDEX__][item_id]" class="form-select" required><option value="">Item</option>@foreach ($items as $item)<option value="{{ $item->id }}">{{ $item->item_code }} — {{ $item->item_name }}</option>@endforeach</select></div>
<div class="col-md-3"><input type="number" step="0.0001" class="form-control" name="items[__INDEX__][quantity]" required></div>
<div class="col-md-2"><input type="number" step="0.0001" class="form-control" name="items[__INDEX__][rate]" placeholder="Rate (avg if blank)"></div>
<div class="col-md-2"><button type="button" class="btn btn-danger-light btn-remove-line"><i class="ri-close-line"></i></button></div>
</div></template>
