@extends('admin.layouts.app')
@section('title', 'Scan Exceptions')
@section('content')
<div class="my-4 page-header-breadcrumb"><div><h1 class="page-title fw-semibold fs-18 mb-0">Open Scan Exceptions</h1><x-admin.module-intro /></div></div>
<div class="card custom-card"><div class="card-body table-responsive">
<table class="table table-bordered">
<thead><tr><th>Code</th><th>Context</th><th>Reason</th><th>Device</th><th>When</th><th></th></tr></thead>
<tbody>
@forelse($exceptions as $exception)
<tr>
<td>{{ $exception->scan_code }}</td>
<td>{{ $exception->context }}</td>
<td>{{ $exception->reason }}</td>
<td>{{ $exception->device_id ?? '—' }}</td>
<td>{{ $exception->created_at?->toDateTimeString() }}</td>
<td>
<form data-ajax="1" data-reload="1" method="post" action="{{ route('admin.scan-exceptions.resolve', $exception) }}" class="d-inline">
@csrf
<button class="btn btn-sm btn-success" type="submit">Resolve</button>
</form>
</td>
</tr>
@empty
<tr><td colspan="6" class="text-muted">No open scan exceptions.</td></tr>
@endforelse
</tbody>
</table>
</div></div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/gap-close/forms.js') }}"></script>
@endpush
