@extends('admin.layouts.app')
@section('title', 'Holidays & Leave')
@section('content')
<div class="my-4 page-header-breadcrumb"><h1 class="page-title fw-semibold fs-18 mb-0">Holidays & Leave ({{ $year }})</h1></div>
<div class="row">
<div class="col-lg-6">
<div class="card custom-card mb-3"><div class="card-body">
<form data-ajax="1" data-reload="1" method="post" action="{{ route('admin.holidays.store') }}">
@csrf
<div class="row g-2">
<div class="col-md-4"><input type="date" name="holiday_date" class="form-control" required></div>
<div class="col-md-5"><input name="name" class="form-control" placeholder="Holiday name" required></div>
<div class="col-md-3"><button class="btn btn-primary w-100" type="submit">Add</button></div>
</div>
</form>
<table class="table table-bordered mt-3"><thead><tr><th>Date</th><th>Name</th></tr></thead>
<tbody>
@foreach($holidays as $holiday)
<tr><td>{{ $holiday->holiday_date->toDateString() }}</td><td>{{ $holiday->name }}</td></tr>
@endforeach
</tbody></table>
</div></div>
</div>
<div class="col-lg-6">
<div class="card custom-card"><div class="card-body">
<form data-ajax="1" data-reload="1" method="post" action="{{ route('admin.leave-balances.store') }}">
@csrf
<input type="hidden" name="year" value="{{ $year }}">
<div class="row g-2">
<div class="col-md-5">
<select name="employee_id" class="form-select" required>
<option value="">Employee</option>
@foreach($employees as $employee)
<option value="{{ $employee->id }}">{{ $employee->employee_code }} — {{ $employee->full_name }}</option>
@endforeach
</select>
</div>
<div class="col-md-3"><input type="number" step="0.5" name="opening_days" class="form-control" placeholder="Opening" required></div>
<div class="col-md-2"><input type="number" step="0.5" name="availed_days" class="form-control" placeholder="Availed" value="0"></div>
<div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Save</button></div>
</div>
</form>
<table class="table table-bordered mt-3"><thead><tr><th>Employee</th><th>Type</th><th>Opening</th><th>Availed</th><th>Balance</th></tr></thead>
<tbody>
@foreach($leaveBalances as $balance)
<tr>
<td>{{ $balance->employee?->full_name }}</td>
<td>{{ $balance->leave_type }}</td>
<td>{{ $balance->opening_days }}</td>
<td>{{ $balance->availed_days }}</td>
<td>{{ number_format((float)$balance->opening_days - (float)$balance->availed_days, 2) }}</td>
</tr>
@endforeach
</tbody></table>
</div></div>
</div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/gap-close/forms.js') }}"></script>
@endpush
