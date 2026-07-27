@extends('admin.layouts.app')
@section('title', 'Purchase RFQs')
@section('content')
<div class="my-4 page-header-breadcrumb"><h1 class="page-title fw-semibold fs-18 mb-0">Purchase RFQs</h1></div>
<div class="card custom-card"><div class="card-body table-responsive">
<table class="table table-bordered">
<thead><tr><th>No</th><th>Date</th><th>Warehouse</th><th>Status</th><th>Lines</th><th>Quotes</th></tr></thead>
<tbody>
@forelse($rfqs as $rfq)
<tr>
<td><a href="{{ route('admin.purchase-rfqs.show', $rfq) }}">{{ $rfq->document_no }}</a></td>
<td>{{ $rfq->document_date?->toDateString() }}</td>
<td>{{ $rfq->warehouse?->name }}</td>
<td>{{ $rfq->status->label() }}</td>
<td>{{ $rfq->items->count() }}</td>
<td>{{ $rfq->quotes_count ?? 0 }}</td>
</tr>
@empty
<tr><td colspan="6" class="text-muted">No RFQs yet.</td></tr>
@endforelse
</tbody>
</table>
</div></div>
@endsection
