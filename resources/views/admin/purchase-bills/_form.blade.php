@php
    $isDraft = ! $purchaseBill || $purchaseBill->status->isEditable();
    $lines = old('items', $purchaseBill?->items?->map(fn ($l) => [
        'goods_receipt_item_id' => $l->goods_receipt_item_id,
        'uom_id' => $l->uom_id,
        'quantity' => $l->quantity,
        'rate' => $l->rate,
        'gst_rate' => $l->gst_rate,
        'po_rate' => $l->po_rate,
        'grn_qty' => $l->grn_qty,
        'match_status' => $l->match_status->label(),
        'item_label' => ($l->item?->item_code ?? '').' — '.($l->item?->item_name ?? ''),
    ])->toArray() ?? []);
    if ($lines === [] && ! empty($billableLines)) {
        $lines = collect($billableLines)->map(fn ($p) => [
            'goods_receipt_item_id' => $p['goods_receipt_item_id'],
            'uom_id' => $p['uom_id'],
            'quantity' => $p['quantity'],
            'rate' => $p['rate'],
            'gst_rate' => $p['gst_rate'],
            'po_rate' => $p['po_rate'],
            'grn_qty' => $p['grn_qty'],
            'match_status' => '—',
            'item_label' => ($p['item_code'] ?? '').' — '.($p['item_name'] ?? ''),
        ])->all();
    }
@endphp
<div class="card custom-card"><div class="card-body">
<form id="purchaseBillForm" action="{{ $action }}" method="POST" novalidate>
@csrf
<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3 mb-3">
    <div class="col-md-3"><label class="form-label">Bill Date *</label><input type="date" class="form-control" name="document_date" value="{{ old('document_date', optional($purchaseBill?->document_date)->format('Y-m-d') ?? now()->toDateString()) }}" {{ $isDraft ? '' : 'readonly' }} required></div>
    <div class="col-md-5"><label class="form-label">Goods Receipt *</label>
        <select name="goods_receipt_id" id="goodsReceiptId" class="form-select select2" {{ $purchaseBill ? 'disabled' : '' }} required>
            <option value="">Select posted GRN</option>
            @foreach ($goodsReceipts as $grn)
                <option value="{{ $grn->id }}" @selected((string) old('goods_receipt_id', $selectedGoodsReceiptId ?? $purchaseBill?->goods_receipt_id) === (string) $grn->id)>
                    {{ $grn->document_no }} — {{ $grn->supplier?->party_name }}
                </option>
            @endforeach
        </select>
        @if ($purchaseBill)<input type="hidden" name="goods_receipt_id" value="{{ $purchaseBill->goods_receipt_id }}">@endif
    </div>
    <div class="col-md-2"><label class="form-label">Supplier Bill No *</label><input type="text" class="form-control" name="supplier_bill_no" value="{{ old('supplier_bill_no', $purchaseBill?->supplier_bill_no) }}" {{ $isDraft ? '' : 'readonly' }} required></div>
    <div class="col-md-2"><label class="form-label">Supplier Bill Date *</label><input type="date" class="form-control" name="supplier_bill_date" value="{{ old('supplier_bill_date', optional($purchaseBill?->supplier_bill_date)->format('Y-m-d') ?? now()->toDateString()) }}" {{ $isDraft ? '' : 'readonly' }} required></div>
    <div class="col-md-3"><label class="form-label">Other Charges</label><input type="number" step="0.01" min="0" class="form-control" name="other_charges" value="{{ old('other_charges', $purchaseBill?->other_charges ?? 0) }}" {{ $isDraft ? '' : 'readonly' }}></div>
    <div class="col-md-9"><label class="form-label">Remarks</label><input type="text" class="form-control" name="remarks" value="{{ old('remarks', $purchaseBill?->remarks) }}" {{ $isDraft ? '' : 'readonly' }}></div>
</div>
<div class="d-flex align-items-center justify-content-between mb-2">
    <h6 class="mb-0">Bill Lines</h6>
    <span class="text-muted fs-12">Match tolerance: rate {{ $rateTolerance }}% · qty {{ $qtyTolerance }}%</span>
</div>
<div class="table-responsive">
<table class="table table-bordered align-middle">
<thead><tr><th>Item</th><th>GRN Qty</th><th>Billed Qty *</th><th>PO Rate</th><th>Billed Rate *</th><th>GST %</th><th>Match</th></tr></thead>
<tbody id="lineRows">
@forelse ($lines as $index => $line)
<tr class="line-row">
    <td>
        <input type="hidden" name="items[{{ $index }}][goods_receipt_item_id]" value="{{ $line['goods_receipt_item_id'] }}">
        <input type="hidden" name="items[{{ $index }}][uom_id]" value="{{ $line['uom_id'] }}">
        <input type="text" class="form-control" value="{{ $line['item_label'] ?? '' }}" readonly>
    </td>
    <td><input type="text" class="form-control" value="{{ $line['grn_qty'] ?? '' }}" readonly></td>
    <td><input type="number" step="0.0001" class="form-control" name="items[{{ $index }}][quantity]" value="{{ $line['quantity'] ?? '' }}" {{ $isDraft ? '' : 'readonly' }} required></td>
    <td><input type="text" class="form-control" value="{{ $line['po_rate'] ?? '' }}" readonly></td>
    <td><input type="number" step="0.0001" class="form-control" name="items[{{ $index }}][rate]" value="{{ $line['rate'] ?? '' }}" {{ $isDraft ? '' : 'readonly' }} required></td>
    <td><input type="number" step="0.01" class="form-control" name="items[{{ $index }}][gst_rate]" value="{{ $line['gst_rate'] ?? 0 }}" {{ $isDraft ? '' : 'readonly' }}></td>
    <td>{{ $line['match_status'] ?? '—' }}</td>
</tr>
@empty
<tr id="emptyLinesHint"><td colspan="7" class="text-muted">Select a posted goods receipt to load billable quantities.</td></tr>
@endforelse
</tbody>
</table>
</div>
@if ($purchaseBill)
<div class="row gy-2 mt-3">
    <div class="col-md-3"><span class="text-muted d-block fs-12">Taxable</span><strong>{{ number_format((float) $purchaseBill->subtotal, 2) }}</strong></div>
    <div class="col-md-3"><span class="text-muted d-block fs-12">Tax</span><strong>{{ number_format((float) $purchaseBill->tax_total, 2) }}</strong></div>
    <div class="col-md-3"><span class="text-muted d-block fs-12">Round Off</span><strong>{{ number_format((float) $purchaseBill->round_off, 2) }}</strong></div>
    <div class="col-md-3"><span class="text-muted d-block fs-12">Grand Total</span><strong>{{ number_format((float) $purchaseBill->grand_total, 2) }}</strong></div>
</div>
@if ($purchaseBill->mismatch_reason)
<p class="text-muted mt-3 mb-0">Mismatch approved: {{ $purchaseBill->mismatch_reason }}</p>
@endif
@endif
@if ($isDraft)
<div class="mt-3"><button class="btn btn-primary" type="submit">Save Draft</button><a href="{{ route('admin.purchase-bills.index') }}" class="btn btn-light">Cancel</a></div>
@endif
</form>
</div></div>
