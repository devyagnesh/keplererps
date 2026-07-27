@php
    $isDraft = ! $salesReturn || $salesReturn->status->value === 'draft';
    $lines = old('items', $salesReturn?->items?->map(fn ($l) => [
        'sales_invoice_item_id' => $l->sales_invoice_item_id,
        'item_id' => $l->item_id,
        'uom_id' => $l->uom_id,
        'batch_id' => $l->batch_id,
        'batch_no' => $l->batch?->batch_no,
        'quantity' => $l->quantity,
        'rate' => $l->rate,
        'gst_rate' => $l->gst_rate,
        'item_label' => ($l->item?->item_code ?? '').' — '.($l->item?->item_name ?? ''),
    ])->toArray() ?? []);
    if ($lines === [] && ! empty($returnableLines)) {
        $lines = collect($returnableLines)->map(fn ($p) => [
            'sales_invoice_item_id' => $p['sales_invoice_item_id'],
            'item_id' => $p['item_id'],
            'uom_id' => $p['uom_id'],
            'batch_id' => null,
            'batch_no' => null,
            'quantity' => $p['quantity'],
            'rate' => $p['rate'],
            'gst_rate' => $p['gst_rate'],
            'item_label' => ($p['item_code'] ?? '').' — '.($p['item_name'] ?? ''),
        ])->all();
    }
@endphp
<div class="card custom-card"><div class="card-body">
<form id="salesReturnForm" action="{{ $action }}" method="POST" novalidate>
@csrf
<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3 mb-3">
    <div class="col-md-3"><label class="form-label">Return Date *</label><input type="date" class="form-control" name="document_date" value="{{ old('document_date', optional($salesReturn?->document_date)->format('Y-m-d') ?? now()->toDateString()) }}" {{ $isDraft ? '' : 'readonly' }} required></div>
    <div class="col-md-5"><label class="form-label">Sales Invoice *</label>
        <select name="sales_invoice_id" id="salesInvoiceId" class="form-select select2" {{ $salesReturn ? 'disabled' : '' }} required>
            <option value="">Select invoice</option>
            @foreach ($salesInvoices as $invoice)
                <option value="{{ $invoice->id }}" @selected((string) old('sales_invoice_id', $selectedSalesInvoiceId ?? $salesReturn?->sales_invoice_id) === (string) $invoice->id)>
                    {{ $invoice->document_no }} — {{ $invoice->customer?->party_name }}
                </option>
            @endforeach
        </select>
        @if ($salesReturn)<input type="hidden" name="sales_invoice_id" value="{{ $salesReturn->sales_invoice_id }}">@endif
    </div>
    <div class="col-md-4"><label class="form-label">Receive Into Warehouse *</label>
        <select name="warehouse_id" class="form-select select2" {{ $isDraft ? '' : 'disabled' }} required>
            <option value="">Select warehouse</option>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id', $salesReturn?->warehouse_id) === (string) $warehouse->id)>
                    {{ $warehouse->code }} — {{ $warehouse->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6"><label class="form-label">Reason *</label><input type="text" class="form-control" name="reason" value="{{ old('reason', $salesReturn?->reason) }}" {{ $isDraft ? '' : 'readonly' }} required></div>
    <div class="col-md-6"><label class="form-label">Remarks</label><input type="text" class="form-control" name="remarks" value="{{ old('remarks', $salesReturn?->remarks) }}" {{ $isDraft ? '' : 'readonly' }}></div>
</div>
<div class="mb-2"><h6 class="mb-0">Return Lines</h6></div>
<div class="table-responsive">
<table class="table table-bordered align-middle">
<thead><tr><th>Item</th><th>Batch</th><th>Return Qty *</th><th>Rate *</th><th>GST %</th></tr></thead>
<tbody id="lineRows">
@forelse ($lines as $index => $line)
<tr class="line-row">
    <td>
        <input type="hidden" name="items[{{ $index }}][sales_invoice_item_id]" value="{{ $line['sales_invoice_item_id'] }}">
        <input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $line['item_id'] }}">
        <input type="hidden" name="items[{{ $index }}][uom_id]" value="{{ $line['uom_id'] }}">
        <input type="text" class="form-control" value="{{ $line['item_label'] ?? '' }}" readonly>
    </td>
    <td>
        <select name="items[{{ $index }}][batch_id]" class="form-select line-batch" data-item-id="{{ $line['item_id'] }}" {{ $isDraft ? '' : 'disabled' }}>
            <option value="">No batch</option>
            @foreach ($batches as $batch)
                @if ((int) $batch->item_id === (int) $line['item_id'])
                    <option value="{{ $batch->id }}" @selected((string) ($line['batch_id'] ?? '') === (string) $batch->id)>{{ $batch->batch_no }}</option>
                @endif
            @endforeach
        </select>
    </td>
    <td><input type="number" step="0.0001" class="form-control" name="items[{{ $index }}][quantity]" value="{{ $line['quantity'] ?? '' }}" {{ $isDraft ? '' : 'readonly' }} required></td>
    <td><input type="number" step="0.0001" class="form-control" name="items[{{ $index }}][rate]" value="{{ $line['rate'] ?? '' }}" {{ $isDraft ? '' : 'readonly' }} required></td>
    <td><input type="number" step="0.01" class="form-control" name="items[{{ $index }}][gst_rate]" value="{{ $line['gst_rate'] ?? 0 }}" {{ $isDraft ? '' : 'readonly' }}></td>
</tr>
@empty
<tr id="emptyLinesHint"><td colspan="5" class="text-muted">Select an invoice to load returnable quantities.</td></tr>
@endforelse
</tbody>
</table>
</div>
@if ($salesReturn)
<div class="row gy-2 mt-3">
    <div class="col-md-4"><span class="text-muted d-block fs-12">Taxable</span><strong>{{ number_format((float) $salesReturn->subtotal, 2) }}</strong></div>
    <div class="col-md-4"><span class="text-muted d-block fs-12">Tax</span><strong>{{ number_format((float) $salesReturn->tax_total, 2) }}</strong></div>
    <div class="col-md-4"><span class="text-muted d-block fs-12">Grand Total</span><strong>{{ number_format((float) $salesReturn->grand_total, 2) }}</strong></div>
</div>
@endif
@if ($isDraft)
<div class="mt-3"><button class="btn btn-primary" type="submit">Save Draft</button><a href="{{ route('admin.sales-returns.index') }}" class="btn btn-light">Cancel</a></div>
@endif
</form>
</div></div>
