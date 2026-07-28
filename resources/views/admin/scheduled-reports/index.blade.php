@extends('admin.layouts.app')
@section('title', 'Scheduled Reports')
@section('content')
<div class="my-4 page-header-breadcrumb"><div><h1 class="page-title fw-semibold fs-18 mb-0">Scheduled Reports</h1><x-admin.module-intro /></div></div>
<div class="card custom-card mb-3"><div class="card-body">
<form data-ajax="1" data-reload="1" method="post" action="{{ route('admin.scheduled-reports.store') }}">
@csrf
<div class="row g-2 align-items-end">
<div class="col-md-3"><input name="name" class="form-control" placeholder="Report name" required></div>
<div class="col-md-2">
<select name="register_key" class="form-select" required>
<option value="">Register</option>
@foreach(['sales','purchase','stock','production','rejection','pending-sales-orders','pending-purchase-orders','stock-valuation','slow-moving','day-book'] as $key)
<option value="{{ $key }}">{{ $key }}</option>
@endforeach
</select>
</div>
<div class="col-md-2">
<select name="frequency" class="form-select" required>
<option value="daily">Daily</option>
<option value="weekly">Weekly</option>
<option value="monthly">Monthly</option>
</select>
</div>
<div class="col-md-3"><input name="recipient_emails" class="form-control" placeholder="email@example.com, ..." required></div>
<div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Add</button></div>
</div>
</form>
</div></div>
<div class="card custom-card"><div class="card-body table-responsive">
<table class="table table-bordered">
<thead><tr><th>Name</th><th>Register</th><th>Frequency</th><th>Recipients</th><th>Last Run</th><th>Active</th><th></th></tr></thead>
<tbody>
@forelse($reports as $report)
<tr>
<td>{{ $report->name }}</td>
<td>{{ $report->register_key }}</td>
<td>{{ ucfirst($report->frequency) }}</td>
<td>{{ $report->recipient_emails }}</td>
<td>{{ $report->last_run_at?->toDateTimeString() ?? '—' }}</td>
<td>{{ $report->is_active ? 'Yes' : 'No' }}</td>
<td>
<form data-ajax="1" data-reload="1" method="post" action="{{ route('admin.scheduled-reports.destroy', $report) }}" class="d-inline">
@csrf @method('DELETE')
<button class="btn btn-sm btn-danger" type="submit">Delete</button>
</form>
</td>
</tr>
@empty
<tr><td colspan="7" class="text-muted">No scheduled reports yet.</td></tr>
@endforelse
</tbody>
</table>
</div></div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/gap-close/forms.js') }}"></script>
@endpush
