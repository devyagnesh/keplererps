@extends('admin.layouts.app')
@section('title', 'GSTR-2B Import')
@section('content')
<div class="my-4 page-header-breadcrumb"><h1 class="page-title fw-semibold fs-18 mb-0">GSTR-2B Import</h1></div>
<div class="card custom-card mb-3"><div class="card-body">
<form data-ajax="1" data-reload="1" method="post" action="{{ route('admin.gstr2b.store') }}" enctype="multipart/form-data">
@csrf
<div class="row g-2">
<div class="col-md-3"><input name="period" class="form-control" placeholder="YYYY-MM" value="{{ now()->format('Y-m') }}" required></div>
<div class="col-md-5"><input type="file" name="file" class="form-control" accept=".csv,text/csv" required></div>
<div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Import</button></div>
</div>
<p class="text-muted fs-12 mt-2 mb-0">CSV columns: gstin, invoice_no, invoice_date, taxable_value, igst, cgst, sgst</p>
</form>
</div></div>
<div class="card custom-card"><div class="card-body table-responsive">
<table class="table table-bordered"><thead><tr><th>Period</th><th>File</th><th>Rows</th><th>Matched</th><th>Mismatch</th><th>When</th></tr></thead>
<tbody>
@forelse($imports as $import)
<tr>
<td>{{ $import->period }}</td>
<td>{{ $import->original_filename }}</td>
<td>{{ $import->row_count }}</td>
<td>{{ $import->matched_count }}</td>
<td>{{ $import->mismatch_count }}</td>
<td>{{ $import->created_at }}</td>
</tr>
@empty
<tr><td colspan="6" class="text-muted">No imports yet.</td></tr>
@endforelse
</tbody></table>
</div></div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/gap-close/forms.js') }}"></script>
@endpush
