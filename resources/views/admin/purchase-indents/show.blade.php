@extends('admin.layouts.app')
@section('title', 'Indent '.$indent->document_no)
@section('content')
<div class="my-4 page-header-breadcrumb d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Indent {{ $indent->document_no }}</h1><x-admin.module-intro /></div>
    <div class="d-flex gap-2">
        @if(in_array($indent->status->value, ['approved', 'partially_ordered'], true))
            <form data-ajax="1" data-reload="0" method="post" action="{{ route('admin.purchase-indents.rfq', $indent) }}" class="d-inline">
                @csrf
                <button class="btn btn-sm btn-primary">Raise RFQ</button>
            </form>
        @endif
    </div>
</div>
<div class="card custom-card"><div class="card-body">
<p>Status: <strong>{{ $indent->status->label() }}</strong> · Warehouse: {{ $indent->warehouse?->name }} · Date: {{ $indent->document_date?->toDateString() }}</p>
<table class="table table-bordered"><thead><tr><th>Item</th><th>Qty</th><th>Base Qty</th><th>Ordered</th><th>Source</th></tr></thead>
<tbody>
@foreach($indent->items as $line)
<tr>
    <td>{{ $line->item?->item_code }} — {{ $line->item?->item_name }}</td>
    <td>{{ number_format((float)$line->quantity, 4) }} {{ $line->uom?->code }}</td>
    <td>{{ number_format((float)($line->base_qty ?? $line->quantity), 4) }}</td>
    <td>{{ number_format((float)$line->ordered_qty, 4) }}</td>
    <td>{{ $line->source }}</td>
</tr>
@endforeach
</tbody></table>
</div></div>
@endsection
