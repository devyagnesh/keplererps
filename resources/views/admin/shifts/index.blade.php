@extends('admin.layouts.app')
@section('title', 'Shifts')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Shifts</h1>
</div>

<div class="row">
<div class="col-xl-7">
    <div class="card custom-card">
        <div class="card-header"><div class="card-title">Shift Master</div></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered text-nowrap w-100">
                <thead><tr><th>Code</th><th>Name</th><th>Timing</th><th>Break</th><th>Paid Hours</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                @forelse ($shifts as $shift)
                    <tr>
                        <td>{{ $shift->code }}</td>
                        <td>{{ $shift->name }}</td>
                        <td>{{ $shift->start_time }} – {{ $shift->end_time }}</td>
                        <td>{{ $shift->break_minutes }} min</td>
                        <td>{{ number_format($shift->durationHours(), 2) }}</td>
                        <td>
                            <span class="badge {{ $shift->is_active ? 'bg-success-transparent' : 'bg-danger-transparent' }}">
                                {{ $shift->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <div class="hstack gap-2 fs-15">
                                @can('shift.update')
                                <button type="button" class="btn btn-icon btn-sm btn-primary-light btn-edit-shift"
                                    data-url="{{ route('admin.shifts.update', $shift) }}"
                                    data-shift="{{ json_encode([
                                        'code' => $shift->code,
                                        'name' => $shift->name,
                                        'start_time' => substr((string) $shift->start_time, 0, 5),
                                        'end_time' => substr((string) $shift->end_time, 0, 5),
                                        'break_minutes' => $shift->break_minutes,
                                        'is_active' => $shift->is_active,
                                    ]) }}"><i class="ri-pencil-line"></i></button>
                                @endcan
                                @can('shift.delete')
                                <button type="button" class="btn btn-icon btn-sm btn-danger-light btn-delete-shift"
                                    data-url="{{ route('admin.shifts.destroy', $shift) }}"><i class="ri-delete-bin-line"></i></button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-muted">No shifts defined yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="col-xl-5">
    @canany(['shift.create', 'shift.update'])
    <div class="card custom-card">
        <div class="card-header"><div class="card-title" id="shiftFormTitle">Add Shift</div></div>
        <div class="card-body">
            <form id="shiftForm" action="{{ route('admin.shifts.store') }}" method="POST" novalidate>
                @csrf
                <input type="hidden" name="_method" value="POST">
                <div class="row gy-3">
                    <div class="col-6"><label class="form-label">Code *</label>
                        <input type="text" class="form-control text-uppercase" name="code" maxlength="20" required>
                    </div>
                    <div class="col-6"><label class="form-label">Name *</label>
                        <input type="text" class="form-control" name="name" maxlength="60" required>
                    </div>
                    <div class="col-6"><label class="form-label">Start *</label>
                        <input type="time" class="form-control" name="start_time" required>
                    </div>
                    <div class="col-6"><label class="form-label">End *</label>
                        <input type="time" class="form-control" name="end_time" required>
                    </div>
                    <div class="col-6"><label class="form-label">Break (minutes)</label>
                        <input type="number" min="0" max="480" class="form-control" name="break_minutes" value="0">
                    </div>
                    <div class="col-6 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="shiftActive" checked>
                            <label class="form-check-label" for="shiftActive">Active</label>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Save Shift</button>
                    <button type="button" class="btn btn-light d-none" id="btnCancelShiftEdit">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    @endcanany
</div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/shifts/shifts.js') }}"></script>
@endpush
