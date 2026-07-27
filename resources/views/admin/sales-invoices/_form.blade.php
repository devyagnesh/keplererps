@php
    $isDraft = ! $salesInvoice || $salesInvoice->status->value === 'draft';
    $lines = old('items', $salesInvoice?->items?->map(fn ($l) => [
        'sales_order_item_id' => $l->sales_order_item_id,
        'quantity' => $l->quantity,
        'rate' => $l->rate,
        'discount_percent' => $l->discount_percent,
        'gst_rate' => $l->gst_rate,
        'item_label' => ($l->item?->item_code ?? '').' — '.($l->item?->item_name ?? ''),
    ])->toArray() ?? []);
    if ($lines === [] && ! empty($pendingLines)) {
        $lines = collect($pendingLines)->map(fn ($p) => [
            'sales_order_item_id' => $p['sales_order_item_id'],
            'quantity' => $p['pending_qty'],
            'rate' => $p['rate'],
            'discount_percent' => $p['discount_percent'] ?? 0,
            'gst_rate' => $p['gst_rate'] ?? 0,
            'item_label' => ($p['item_code'] ?? '').' — '.($p['item_name'] ?? ''),
        ])->all();
    }
@endphp
<div class="card custom-card"><div class="card-body">
<form id="salesInvoiceForm" action="{{ $action }}" method="POST" novalidate data-pending-lines-url="{{ url('/admin/sales-invoices/pending-lines') }}">
@csrf
<input type="hidden" name="_method" value="{{ $method }}">
@if (! empty($selectedDeliveryChallanId) || $salesInvoice?->delivery_challan_id)
<input type="hidden" name="delivery_challan_id" value="{{ old('delivery_challan_id', $selectedDeliveryChallanId ?? $salesInvoice?->delivery_challan_id) }}">
@endif
<div class="row gy-3 mb-3">
    <div class="col-md-3"><label class="form-label">Date *</label><input type="date" class="form-control" name="document_date" value="{{ old('document_date', optional($salesInvoice?->document_date)->format('Y-m-d') ?? now()->toDateString()) }}" {{ $isDraft ? '' : 'readonly' }} required></div>
    <div class="col-md-5"><label class="form-label">Sales Order *</label>
        <select name="sales_order_id" id="salesOrderId" class="form-select select2" {{ $salesInvoice || ! empty($selectedDeliveryChallanId) ? 'disabled' : '' }} required>
            <option value="">Select sales order</option>
            @foreach ($salesOrders as $order)
                <option value="{{ $order->id }}" @selected((string) old('sales_order_id', $selectedSalesOrderId ?? $salesInvoice?->sales_order_id) === (string) $order->id)>
                    {{ $order->document_no }} — {{ $order->customer?->party_name }}
                </option>
            @endforeach
        </select>
        @if ($salesInvoice || ! empty($selectedDeliveryChallanId))
            <input type="hidden" name="sales_order_id" value="{{ $selectedSalesOrderId ?? $salesInvoice?->sales_order_id }}">
        @endif
    </div>
    @if (! empty($selectedDeliveryChallanId) || $salesInvoice?->delivery_challan_id)
    <div class="col-md-4"><label class="form-label">Delivery Challan</label>
        <input type="text" class="form-control" value="{{ ($deliveryChallans ?? collect())->firstWhere('id', $selectedDeliveryChallanId ?? $salesInvoice?->delivery_challan_id)?->document_no ?? ('#'.($selectedDeliveryChallanId ?? $salesInvoice?->delivery_challan_id)) }}" readonly>
    </div>
    @endif
    @if ($salesInvoice)
    <div class="col-md-4"><label class="form-label">Customer</label><input type="text" class="form-control" value="{{ $salesInvoice->customer?->party_code }} — {{ $salesInvoice->customer?->party_name }}" readonly></div>
    <div class="col-md-4"><label class="form-label">Warehouse</label><input type="text" class="form-control" value="{{ $salesInvoice->warehouse?->code }} — {{ $salesInvoice->warehouse?->name }}" readonly></div>
    <div class="col-md-4"><label class="form-label">Place of Supply</label><input type="text" class="form-control" value="{{ $salesInvoice->placeOfSupplyState?->code }} — {{ $salesInvoice->placeOfSupplyState?->name }}" readonly></div>
    @endif
    <div class="col-md-12"><label class="form-label">Remarks</label><input type="text" class="form-control" name="remarks" value="{{ old('remarks', $salesInvoice?->remarks) }}" {{ $isDraft ? '' : 'readonly' }}></div>
</div>
<div class="mb-2"><h6 class="mb-0">Invoice Lines</h6></div>
<div id="lineRows">
@forelse ($lines as $index => $line)
<div class="row g-2 mb-2 line-row">
    <input type="hidden" name="items[{{ $index }}][sales_order_item_id]" value="{{ $line['sales_order_item_id'] }}">
    <div class="col-md-3"><input type="text" class="form-control" value="{{ $line['item_label'] ?? '' }}" readonly></div>
    <div class="col-md-2"><input type="number" step="0.0001" min="0.0001" class="form-control invoice-qty" name="items[{{ $index }}][quantity]" value="{{ $line['quantity'] ?? '' }}" placeholder="Qty" {{ $isDraft ? '' : 'readonly' }} required></div>
    <div class="col-md-2"><input type="number" step="0.0001" min="0" class="form-control" name="items[{{ $index }}][rate]" value="{{ $line['rate'] ?? '' }}" placeholder="Rate" {{ $isDraft ? '' : 'readonly' }} required></div>
    <div class="col-md-2"><input type="number" step="0.01" min="0" max="100" class="form-control" name="items[{{ $index }}][discount_percent]" value="{{ $line['discount_percent'] ?? 0 }}" placeholder="Disc %" {{ $isDraft ? '' : 'readonly' }}></div>
    <div class="col-md-2"><input type="number" step="0.01" min="0" class="form-control" name="items[{{ $index }}][gst_rate]" value="{{ $line['gst_rate'] ?? '' }}" placeholder="GST" {{ $isDraft ? '' : 'readonly' }}></div>
</div>
@empty
<p class="text-muted" id="emptyLinesHint">Select a sales order to load pending invoice quantities.</p>
@endforelse
</div>
@if ($isDraft)
<div class="mt-3"><button class="btn btn-primary" type="submit">Save Draft</button><a href="{{ route('admin.sales-invoices.index') }}" class="btn btn-light">Cancel</a></div>
@endif
</form>
</div></div>
