@extends('admin.layouts.app')
@section('title', 'Activity Log')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Activity Log</h1>
</div>

<div class="card custom-card">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.activity-logs.index') }}" class="row g-2 mb-3">
            <div class="col-md-2">
                <select name="log_name" class="form-select">
                    <option value="">All modules</option>
                    @foreach ($logNames as $name)
                        <option value="{{ $name }}" @selected($filters['log_name'] === $name)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" name="event" class="form-control" placeholder="Event" value="{{ $filters['event'] }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="from_date" class="form-control" value="{{ $filters['from_date'] }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="to_date" class="form-control" value="{{ $filters['to_date'] }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered text-nowrap w-100">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Module</th>
                        <th>Event</th>
                        <th>Description</th>
                        <th>User</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td>{{ $log->created_at?->format('d M Y H:i') }}</td>
                        <td>{{ $log->log_name ?? '—' }}</td>
                        <td>{{ $log->event }}</td>
                        <td class="text-wrap">{{ $log->description }}</td>
                        <td>{{ $log->causer?->name ?? 'System' }}</td>
                        <td>{{ $log->ip_address ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">No activity recorded yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $logs->links() }}
    </div>
</div>
@endsection
