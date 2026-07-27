@extends('admin.layouts.app')
@section('title', 'Supplier Ratings')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Supplier Ratings</h1>
    <form method="post" action="{{ route('admin.supplier-ratings.recompute') }}" data-ajax="1" data-reload="1">@csrf<button class="btn btn-primary btn-sm" type="submit">Recompute</button></form>
</div>
<div class="card custom-card"><div class="card-body"><div class="table-responsive">
<table class="table table-bordered text-nowrap w-100">
<thead><tr><th>Supplier</th><th>Period</th><th>POs</th><th>OTIF %</th><th>Quality %</th><th>Overall</th></tr></thead>
<tbody>
@forelse($ratings as $row)
<tr>
    <td>{{ $row->party?->party_code }} — {{ $row->party?->party_name }}</td>
    <td>{{ $row->period_from?->format('d M Y') }} – {{ $row->period_to?->format('d M Y') }}</td>
    <td>{{ $row->po_count }}</td>
    <td>{{ $row->otif_score }}</td>
    <td>{{ $row->quality_score }}</td>
    <td>{{ $row->overall_score }}</td>
</tr>
@empty
<tr><td colspan="6" class="text-muted">No ratings yet. Click Recompute.</td></tr>
@endforelse
</tbody></table>
</div></div></div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/gap-close/forms.js') }}"></script>
@endpush
