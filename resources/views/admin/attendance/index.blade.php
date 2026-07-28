@extends('admin.layouts.app')
@section('title', 'Daily Attendance')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Daily Attendance</h1>
        <x-admin.module-intro />
    </div>
    <div class="d-flex flex-wrap gap-2">
        @can('attendance.create')
        <a href="{{ route('admin.attendance.import.template') }}" class="btn btn-light btn-sm">CSV Template</a>
        <form id="biometricImportForm" action="{{ route('admin.attendance.import') }}" method="POST" enctype="multipart/form-data" class="d-flex gap-2">
            @csrf
            <input type="file" name="file" accept=".csv,text/csv" class="form-control form-control-sm" required>
            <button type="submit" class="btn btn-outline-primary btn-sm">Import Biometric</button>
        </form>
        @endcan
        <form method="GET" action="{{ route('admin.attendance.index') }}" class="d-flex gap-2">
            <input type="date" class="form-control form-control-sm" name="attendance_date" value="{{ $sheet['date'] }}" max="{{ now()->toDateString() }}">
            <button type="submit" class="btn btn-primary btn-sm">Load Day</button>
        </form>
    </div>
</div>

@if ($sheet['locked'])
<div class="alert alert-warning">Payroll for this month is posted, so attendance is read-only.</div>
@endif

<div class="card custom-card">
<div class="card-body">
<form id="attendanceForm" action="{{ route('admin.attendance.store') }}" method="POST" novalidate>
@csrf
<input type="hidden" name="attendance_date" value="{{ $sheet['date'] }}">

<div class="d-flex flex-wrap gap-2 mb-3">
    <span class="text-muted align-self-center fs-12">Mark all as</span>
    @foreach (\App\Enums\AttendanceStatus::cases() as $status)
        <button type="button" class="btn btn-sm btn-light btn-mark-all" data-status="{{ $status->value }}">{{ $status->label() }}</button>
    @endforeach
</div>

<div class="table-responsive">
<table class="table table-bordered text-nowrap w-100">
    <thead><tr><th>Code</th><th>Employee</th><th>Department</th><th>Shift</th><th>Status</th><th>Worked Hrs</th><th>OT Hrs</th><th>Remarks</th></tr></thead>
    <tbody>
    @forelse ($sheet['rows'] as $index => $row)
        <tr>
            <td>{{ $row['employee_code'] }}
                <input type="hidden" name="rows[{{ $index }}][employee_id]" value="{{ $row['employee_id'] }}">
            </td>
            <td>{{ $row['full_name'] }}</td>
            <td>{{ $row['department'] ?? '—' }}</td>
            <td>
                <select class="form-select form-select-sm" name="rows[{{ $index }}][shift_id]" @disabled($sheet['locked'])>
                    <option value="">None</option>
                    @foreach ($shifts as $shift)
                        <option value="{{ $shift->id }}" @selected((int) $row['shift_id'] === (int) $shift->id)>{{ $shift->code }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <select class="form-select form-select-sm attendance-status" name="rows[{{ $index }}][status]" @disabled($sheet['locked'])>
                    @foreach (\App\Enums\AttendanceStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected($row['status'] === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </td>
            <td><input type="number" step="0.25" min="0" max="24" class="form-control form-control-sm" name="rows[{{ $index }}][worked_hours]" value="{{ $row['worked_hours'] }}" @disabled($sheet['locked'])></td>
            <td><input type="number" step="0.25" min="0" max="12" class="form-control form-control-sm" name="rows[{{ $index }}][overtime_hours]" value="{{ $row['overtime_hours'] }}" @disabled($sheet['locked'])></td>
            <td><input type="text" class="form-control form-control-sm" name="rows[{{ $index }}][remarks]" value="{{ $row['remarks'] }}" @disabled($sheet['locked'])></td>
        </tr>
    @empty
        <tr><td colspan="8" class="text-muted">No employees were on the rolls on this date.</td></tr>
    @endforelse
    </tbody>
</table>
</div>

@can('attendance.create')
    @if (! $sheet['locked'] && count($sheet['rows']) > 0)
    <div class="mt-3">
        <button type="submit" class="btn btn-primary">Save Attendance</button>
    </div>
    @endif
@endcan
</form>
</div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/attendance/attendance.js') }}"></script>
@endpush
