@php
    $isDraft = ! $purchaseReturn || $purchaseReturn->status->value === 'draft';
    $lines = old('items', $purchaseReturn?->items?->map(fn ($l) => [
        'goods_receipt_item_id' => $l->goods_receipt_item_id,
        'batch_id' => $l->batch_id,
        'batch_no' => $l->batch?->batch_no,
        'quantity' => $l->quantity,
        'rate' => $l->rate,
        'gst_rate' => $l->gst_rate,
        'item_label' => ($l->item?->item_code ?? '').' — '.($l->item?->item_name ?? ''),
    ])->toArray() ?? []);
    if ($lines === [] && ! empty($returnableLines)) {
        $lines = collect($returnableLines)->map(fn ($p) => [
            'goods_receipt_item_id' => $p['goods_receipt_item_id'],
            'batch_id' => $p['batch_id'],
            'batch_no' => $p['batch_no'],
            'quantity' => $p['quantity'],
            'rate' => $p['rate'],
            'gst_rate' => $p['gst_rate'],
            'item_label' => ($p['item_code'] ?? '').' — '.($p['item_name'] ?? ''),
        ])->all();
    }
@endphp
<div class="card custom-card"><div class="card-body">
<form id="purchaseReturnForm" action="{{ $action }}" method="POST" novalidate>
@csrf
<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3 mb-3">
    <div class="col-md-3"><label class="form-label">Return Date *</label><input type="date" class="form-control" name="document_date" value="{{ old('document_date', optional($purchaseReturn?->document_date)->format('Y-m-d') ?? now()->toDateString()) }}" {{ $isDraft ? '' : 'readonly' }} required></div>
    <div class="col-md-5"><label class="form-label">Goods Receipt *</label>
        <select name="goods_receipt_id" id="goodsReceiptId" class="form-select select2" {{ $purchaseReturn ? 'disabled' : '' }} required>
            <option value="">Select posted GRN</option>
            @foreach ($goodsReceipts as $grn)
                <option value="{{ $grn->id }}" @selected((string) old('goods_receipt_id', $selectedGoodsReceiptId ?? $purchaseReturn?->goods_receipt_id) === (string) $grn->id)>
                    {{ $grn->document_no }} — {{ $grn->supplier?->party_name }}
                </option>
            @endforeach
        </select>
        @if ($purchaseReturn)<input type="hidden" name="goods_receipt_id" value="{{ $purchaseReturn->goods_receipt_id }}">@endif
    </div>
    <div class="col-md-4"><label class="form-label">Issue From Warehouse</label>
        <select name="warehouse_id" class="form-select select2" {{ $isDraft ? '' : 'disabled' }}>
            <option value="">Use GRN warehouse</option>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id', $purchaseReturn?->warehouse_id) === (string) $warehouse->id)>
                    {{ $warehouse->code }} — {{ $warehouse->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6"><label class="form-label">Reason *</label><input type="text" class="form-control" name="reason" value="{{ old('reason', $purchaseReturn?->reason) }}" {{ $isDraft ? '' : 'readonly' }} required></div>
    <div class="col-md-6"><label class="form-label">Remarks</label><input type="text" class="form-control" name="remarks" value="{{ old('remarks', $purchaseReturn?->remarks) }}" {{ $isDraft ? '' : 'readonly' }}></div>
</div>
<div class="mb-2"><h6 class="mb-0">Return Lines</h6></div>
<div class="table-responsive">
<table class="table table-bordered align-middle">
<thead><tr><th>Item</th><th>Batch</th><th>Return Qty *</th><th>Rate</th><th>GST %</th></tr></thead>
<tbody id="lineRows">
@forelse ($lines as $index => $line)
<tr class="line-row">
    <td>
        <input type="hidden" name="items[{{ $index }}][goods_receipt_item_id]" value="{{ $line['goods_receipt_item_id'] }}">
        <input type="hidden" name="items[{{ $index }}][batch_id]" value="{{ $line['batch_id'] }}">
        <input type="text" class="form-control" value="{{ $line['item_label'] ?? '' }}" readonly>
    </td>
    <td><input type="text" class="form-control" value="{{ $line['batch_no'] ?? '—' }}" readonly></td>
    <td><input type="number" step="0.0001" class="form-control" name="items[{{ $index }}][quantity]" value="{{ $line['quantity'] ?? '' }}" {{ $isDraft ? '' : 'readonly' }} required></td>
    <td><input type="number" step="0.0001" class="form-control" name="items[{{ $index }}][rate]" value="{{ $line['rate'] ?? '' }}" {{ $isDraft ? '' : 'readonly' }}></td>
    <td><input type="number" step="0.01" class="form-control" name="items[{{ $index }}][gst_rate]" value="{{ $line['gst_rate'] ?? 0 }}" {{ $isDraft ? '' : 'readonly' }}></td>
</tr>
@empty
<tr id="emptyLinesHint"><td colspan="5" class="text-muted">Select a posted goods receipt to load returnable quantities.</td></tr>
@endforelse
</tbody>
</table>
</div>
@if ($purchaseReturn)
<div class="row gy-2 mt-3">
    <div class="col-md-4"><span class="text-muted d-block fs-12">Taxable</span><strong>{{ number_format((float) $purchaseReturn->subtotal, 2) }}</strong></div>
    <div class="col-md-4"><span class="text-muted d-block fs-12">Tax</span><strong>{{ number_format((float) $purchaseReturn->tax_total, 2) }}</strong></div>
    <div class="col-md-4"><span class="text-muted d-block fs-12">Grand Total</span><strong>{{ number_format((float) $purchaseReturn->grand_total, 2) }}</strong></div>
</div>
@endif
@if ($isDraft)
<div class="mt-3"><button class="btn btn-primary" type="submit">Save Draft</button><a href="{{ route('admin.purchase-returns.index') }}" class="btn btn-light">Cancel</a></div>
@endif
</form>
</div></div>
