@extends('admin.layouts.app')
@section('title', 'Capacity Chart')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Capacity Chart ({{ $days }} days)</h1>
        <x-admin.module-intro />
    </div>
    <a href="{{ route('admin.shop-floor.operator') }}" class="btn btn-light btn-sm">Operator Board</a>
</div>
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-2">
        <input type="number" name="days" class="form-control" min="1" max="30" value="{{ $days }}">
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary">Refresh</button>
    </div>
</form>
<div class="card custom-card">
    <div class="table-responsive">
        <table class="table table-bordered mb-0">
            <thead>
                <tr>
                    <th>Work Centre</th>
                    <th class="text-end">Planned Hrs</th>
                    <th class="text-end">Available Hrs</th>
                    <th class="text-end">Maintenance Hrs</th>
                    <th class="text-end">Utilization %</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row['work_centre_code'] }} — {{ $row['work_centre_name'] }}</td>
                        <td class="text-end">{{ number_format($row['planned_hours'], 2) }}</td>
                        <td class="text-end">{{ number_format($row['available_hours'], 2) }}</td>
                        <td class="text-end">{{ number_format($row['maintenance_hours'], 2) }}</td>
                        <td class="text-end">{{ number_format($row['utilization_percent'], 1) }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted text-center">No active work centres.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
