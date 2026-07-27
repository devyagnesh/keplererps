@extends('admin.layouts.app')
@section('title', 'New Salary Run')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">New Salary Run</h1>
    <a href="{{ route('admin.salary-runs.index') }}" class="btn btn-light btn-sm">Back</a>
</div>

<div class="card custom-card"><div class="card-body">
<form id="salaryRunForm" action="{{ route('admin.salary-runs.store') }}" method="POST" novalidate>
@csrf
<div class="row gy-3">
    <div class="col-md-3"><label class="form-label">Month *</label>
        <select class="form-select" name="period_month" required>
            @for ($month = 1; $month <= 12; $month++)
                <option value="{{ $month }}" @selected($month === (int) now()->subMonthNoOverflow()->month)>
                    {{ date('F', mktime(0, 0, 0, $month, 1)) }}
                </option>
            @endfor
        </select>
    </div>
    <div class="col-md-3"><label class="form-label">Year *</label>
        <input type="number" class="form-control" name="period_year" min="2000" max="2100" value="{{ now()->subMonthNoOverflow()->year }}" required>
    </div>
    <div class="col-md-3"><label class="form-label">Payment Date *</label>
        <input type="date" class="form-control" name="payment_date" value="{{ now()->toDateString() }}" required>
    </div>
    <div class="col-md-3"><label class="form-label">Remarks</label>
        <input type="text" class="form-control" name="remarks">
    </div>
</div>
<p class="text-muted fs-12 mt-3 mb-0">
    Slips are built for every employee on the rolls at the end of the month and prorated on marked attendance.
    Employees with no attendance marked are paid the full month.
</p>
<div class="mt-3">
    <button class="btn btn-primary" type="submit">Create Run</button>
</div>
</form>
</div></div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/salary-runs/form.js') }}"></script>
@endpush
