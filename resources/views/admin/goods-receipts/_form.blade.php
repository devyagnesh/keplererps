@php
    $isDraft = ! $goodsReceipt || $goodsReceipt->status->value === 'draft';
    $lines = old('items', $goodsReceipt?->items?->map(fn ($l) => [
        'purchase_order_item_id' => $l->purchase_order_item_id,
        'received_qty' => $l->received_qty,
        'accepted_qty' => $l->accepted_qty,
        'rejected_qty' => $l->rejected_qty,
        'rejection_reason' => $l->rejection_reason,
        'rate' => $l->rate,
        'batch_no' => $l->batch_no,
        'serial_no' => $l->serial_no,
        'item_label' => ($l->item?->item_code ?? '').' — '.($l->item?->item_name ?? ''),
    ])->toArray() ?? []);
    if ($lines === [] && ! empty($pendingLines)) {
        $lines = collect($pendingLines)->map(fn ($p) => [
            'purchase_order_item_id' => $p['purchase_order_item_id'],
            'received_qty' => $p['pending_qty'],
            'accepted_qty' => $p['pending_qty'],
            'rejected_qty' => 0,
            'rejection_reason' => '',
            'rate' => $p['rate'],
            'batch_no' => '',
            'serial_no' => '',
            'item_label' => ($p['item_code'] ?? '').' — '.($p['item_name'] ?? ''),
        ])->all();
    }
@endphp
<div class="card custom-card"><div class="card-body">
<form id="grnForm" action="{{ $action }}" method="POST" novalidate>
@csrf
<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3 mb-3">
    <div class="col-md-3"><label class="form-label">Date *</label><input type="date" class="form-control" name="document_date" value="{{ old('document_date', optional($goodsReceipt?->document_date)->format('Y-m-d') ?? now()->toDateString()) }}" {{ $isDraft ? '' : 'readonly' }} required></div>
    <div class="col-md-5"><label class="form-label">Purchase Order *</label>
        <select name="purchase_order_id" id="purchaseOrderId" class="form-select select2" {{ $goodsReceipt ? 'disabled' : '' }} required>
            <option value="">Select PO</option>
            @foreach ($purchaseOrders as $po)
                <option value="{{ $po->id }}" @selected((string) old('purchase_order_id', $selectedPurchaseOrderId ?? $goodsReceipt?->purchase_order_id) === (string) $po->id)>
                    {{ $po->document_no }} — {{ $po->supplier?->party_name }}
                </option>
            @endforeach
        </select>
        @if ($goodsReceipt)<input type="hidden" name="purchase_order_id" value="{{ $goodsReceipt->purchase_order_id }}">@endif
    </div>
    <div class="col-md-2"><label class="form-label">Supplier Invoice No *</label><input type="text" class="form-control" name="supplier_invoice_no" value="{{ old('supplier_invoice_no', $goodsReceipt?->supplier_invoice_no) }}" {{ $isDraft ? '' : 'readonly' }} required></div>
    <div class="col-md-2"><label class="form-label">Invoice Date *</label><input type="date" class="form-control" name="supplier_invoice_date" value="{{ old('supplier_invoice_date', optional($goodsReceipt?->supplier_invoice_date)->format('Y-m-d') ?? now()->toDateString()) }}" {{ $isDraft ? '' : 'readonly' }} required></div>
    <div class="col-md-3"><label class="form-label">Vehicle No</label><input type="text" class="form-control" name="vehicle_number" value="{{ old('vehicle_number', $goodsReceipt?->vehicle_number) }}" {{ $isDraft ? '' : 'readonly' }} placeholder="GJ01AB1234"></div>
    <div class="col-md-2"><label class="form-label">Freight</label><input type="number" step="0.01" min="0" class="form-control" name="freight_charges" value="{{ old('freight_charges', $goodsReceipt?->freight_charges ?? 0) }}" {{ $isDraft ? '' : 'readonly' }}></div>
    <div class="col-md-2"><label class="form-label">Other Charges</label><input type="number" step="0.01" min="0" class="form-control" name="other_charges" value="{{ old('other_charges', $goodsReceipt?->other_charges ?? 0) }}" {{ $isDraft ? '' : 'readonly' }}></div>
    <div class="col-md-2"><label class="form-label">Allocate Charges On</label>
        <select name="charge_allocation_basis" class="form-select" {{ $isDraft ? '' : 'disabled' }}>
            @foreach (\App\Enums\ChargeAllocationBasis::cases() as $basis)
                <option value="{{ $basis->value }}" @selected((string) old('charge_allocation_basis', $goodsReceipt?->charge_allocation_basis?->value ?? 'value') === $basis->value)>{{ $basis->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-5"><label class="form-label">Remarks</label><input type="text" class="form-control" name="remarks" value="{{ old('remarks', $goodsReceipt?->remarks) }}" {{ $isDraft ? '' : 'readonly' }}></div>
</div>
<div class="mb-2"><h6 class="mb-0">Receipt Lines</h6></div>
<div id="lineRows">
@forelse ($lines as $index => $line)
<div class="row g-2 mb-2 line-row">
    <input type="hidden" name="items[{{ $index }}][purchase_order_item_id]" value="{{ $line['purchase_order_item_id'] }}">
    <div class="col-md-3"><input type="text" class="form-control" value="{{ $line['item_label'] ?? '' }}" readonly></div>
    <div class="col-md-1"><input type="number" step="0.0001" class="form-control received-qty" name="items[{{ $index }}][received_qty]" value="{{ $line['received_qty'] ?? '' }}" {{ $isDraft ? '' : 'readonly' }} required></div>
    <div class="col-md-1"><input type="number" step="0.0001" class="form-control accepted-qty" name="items[{{ $index }}][accepted_qty]" value="{{ $line['accepted_qty'] ?? '' }}" {{ $isDraft ? '' : 'readonly' }} required></div>
    <div class="col-md-1"><input type="number" step="0.0001" class="form-control" name="items[{{ $index }}][rejected_qty]" value="{{ $line['rejected_qty'] ?? 0 }}" {{ $isDraft ? '' : 'readonly' }}></div>
    <div class="col-md-2"><input type="text" class="form-control" name="items[{{ $index }}][rejection_reason]" value="{{ $line['rejection_reason'] ?? '' }}" placeholder="Reject reason" {{ $isDraft ? '' : 'readonly' }}></div>
    <div class="col-md-1"><input type="number" step="0.0001" class="form-control" name="items[{{ $index }}][rate]" value="{{ $line['rate'] ?? '' }}" {{ $isDraft ? '' : 'readonly' }}></div>
    <div class="col-md-1"><input type="text" class="form-control" name="items[{{ $index }}][batch_no]" value="{{ $line['batch_no'] ?? '' }}" placeholder="Batch" {{ $isDraft ? '' : 'readonly' }}></div>
    <div class="col-md-2"><input type="text" class="form-control" name="items[{{ $index }}][serial_no]" value="{{ $line['serial_no'] ?? '' }}" placeholder="Serial" {{ $isDraft ? '' : 'readonly' }}></div>
</div>
@empty
<p class="text-muted" id="emptyLinesHint">Select a purchase order to load pending quantities.</p>
@endforelse
</div>
@if (! $isDraft && $goodsReceipt && $goodsReceipt->totalCharges() > 0)
<div class="mt-4">
    <h6>Landed Cost Allocation</h6>
    <div class="table-responsive">
    <table class="table table-bordered align-middle">
    <thead><tr><th>Item</th><th>Accepted Qty</th><th>Purchase Rate</th><th>Allocated Charge</th><th>Landed Rate</th></tr></thead>
    <tbody>
    @foreach ($goodsReceipt->items as $line)
    <tr>
        <td>{{ $line->item?->item_code }} — {{ $line->item?->item_name }}</td>
        <td>{{ number_format((float) $line->accepted_qty, 4) }}</td>
        <td>{{ number_format((float) $line->rate, 4) }}</td>
        <td>{{ number_format((float) $line->allocated_charge, 2) }}</td>
        <td>{{ $line->landed_rate !== null ? number_format((float) $line->landed_rate, 4) : '—' }}</td>
    </tr>
    @endforeach
    </tbody>
    </table>
    </div>
</div>
@endif
@if ($isDraft)
<div class="mt-3"><button class="btn btn-primary" type="submit">Save Draft</button><a href="{{ route('admin.goods-receipts.index') }}" class="btn btn-light">Cancel</a></div>
@endif
</form>
</div></div>
