@extends('admin.layouts.app')
@section('title', 'RFQ '.$rfq->document_no)
@section('content')
<div class="my-4 page-header-breadcrumb d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">RFQ {{ $rfq->document_no }}</h1><x-admin.module-intro /></div>
    <div class="d-flex gap-2">
        @if($rfq->status->value === 'draft')
            <form data-ajax="1" data-reload="1" method="post" action="{{ route('admin.purchase-rfqs.mark-sent', $rfq) }}">@csrf<button class="btn btn-sm btn-info">Mark Sent</button></form>
        @endif
    </div>
</div>
<div class="card custom-card mb-3"><div class="card-body">
<p>Status: <strong>{{ $rfq->status->label() }}</strong> · Warehouse: {{ $rfq->warehouse?->name }} · Date: {{ $rfq->document_date?->toDateString() }} · Valid until: {{ $rfq->valid_until?->toDateString() ?? '—' }}</p>
</div></div>

<div class="card custom-card mb-3"><div class="card-header"><div class="card-title">Line Items</div></div><div class="card-body table-responsive">
<table class="table table-bordered"><thead><tr><th>Item</th><th>Qty</th><th>Base Qty</th><th>UOM</th></tr></thead>
<tbody>
@foreach($rfq->items as $line)
<tr>
<td>{{ $line->item?->item_code }} — {{ $line->item?->item_name }}</td>
<td>{{ number_format((float) $line->quantity, 4) }}</td>
<td>{{ number_format((float) ($line->base_qty ?? $line->quantity), 4) }}</td>
<td>{{ $line->uom?->code }}</td>
</tr>
@endforeach
</tbody></table>
</div></div>

@if(!in_array($rfq->status->value, ['awarded', 'cancelled'], true))
<div class="card custom-card mb-3"><div class="card-header"><div class="card-title">Add / Update Supplier Quote</div></div><div class="card-body">
<form data-ajax="1" data-reload="1" method="post" action="{{ route('admin.purchase-rfqs.add-quote', $rfq) }}">
    @csrf
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label">Supplier</label>
            <select name="supplier_id" class="form-select" required>
                <option value="">Select</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->party_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Freight</label>
            <input type="number" step="0.01" min="0" name="freight_amount" class="form-control" value="0">
        </div>
        <div class="col-md-2">
            <label class="form-label">Lead days</label>
            <input type="number" min="0" name="lead_time_days" class="form-control" value="7">
        </div>
    </div>
    <div class="table-responsive mb-3">
        <table class="table table-bordered"><thead><tr><th>Item</th><th>Rate</th></tr></thead>
        <tbody>
        @foreach($rfq->items as $line)
            <tr>
                <td>{{ $line->item?->item_code }}</td>
                <td><input type="number" step="0.0001" min="0" name="rates[{{ $line->id }}]" class="form-control" required></td>
            </tr>
        @endforeach
        </tbody></table>
    </div>
    <button class="btn btn-primary">Save Quote</button>
</form>
</div></div>
@endif

<div class="card custom-card mb-3"><div class="card-header"><div class="card-title">Comparative Statement</div></div><div class="card-body table-responsive">
<table class="table table-bordered">
<thead>
<tr>
<th>Item</th><th>Qty</th>
@foreach($rfq->quotes as $quote)
<th>{{ $quote->supplier?->party_name }}@if($quote->is_selected) ★@endif</th>
@endforeach
<th>L1</th>
</tr>
</thead>
<tbody>
@forelse($comparative as $row)
<tr>
<td>{{ $row['item_code'] }} — {{ $row['item_name'] }}</td>
<td>{{ number_format($row['quantity'], 4) }}</td>
@foreach($row['quotes'] as $q)
<td class="{{ ($q['rate'] !== null && $q['rate'] == $row['lowest_rate']) ? 'table-success' : '' }}">
    {{ $q['rate'] !== null ? number_format($q['rate'], 4) : '—' }}
</td>
@endforeach
<td>{{ $row['lowest_rate'] !== null ? number_format($row['lowest_rate'], 4) : '—' }}</td>
</tr>
@empty
<tr><td colspan="{{ 3 + $rfq->quotes->count() }}" class="text-muted">Add quotes to build the comparative.</td></tr>
@endforelse
</tbody>
</table>
</div></div>

<div class="card custom-card"><div class="card-header"><div class="card-title">Award</div></div><div class="card-body">
@forelse($rfq->quotes as $quote)
    @if(!$quote->is_selected && !in_array($rfq->status->value, ['awarded', 'cancelled'], true))
    <form data-ajax="1" data-reload="1" method="post" action="{{ route('admin.purchase-rfqs.award', [$rfq, $quote]) }}" class="border rounded p-3 mb-2">
        @csrf
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <strong>{{ $quote->supplier?->party_name }}</strong>
                <span class="text-muted">· Total {{ number_format($quote->lineTotal(), 2) }}</span>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <input type="text" name="award_reason" class="form-control form-control-sm" placeholder="Reason if not L1" style="min-width:220px">
                <input type="hidden" name="create_po" value="1">
                <button class="btn btn-sm btn-success">Award + Create PO</button>
            </div>
        </div>
    </form>
    @elseif($quote->is_selected)
        <p class="mb-0 text-success">Awarded to {{ $quote->supplier?->party_name }}@if($quote->award_reason) — {{ $quote->award_reason }}@endif</p>
    @endif
@empty
<p class="text-muted mb-0">No quotes to award.</p>
@endforelse
</div></div>
@endsection
