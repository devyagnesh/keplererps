@extends('admin.layouts.app')
@section('title', 'PM Due')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Preventive Maintenance Due</h1>
    <a href="{{ route('admin.work-centres.index') }}" class="btn btn-light btn-sm">Back</a>
</div>
<div class="card custom-card"><div class="card-body">
    <p class="text-muted">Assets at or above 90% of any configured service interval (US-M11-01).</p>
    <div class="table-responsive">
        <table class="table table-bordered text-nowrap w-100">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Usage ratio</th>
                    <th>Next due</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($assets as $asset)
                    <tr>
                        <td>{{ $asset->code }}</td>
                        <td>{{ $asset->name }}</td>
                        <td>{{ $asset->asset_type->label() }}</td>
                        <td>{{ number_format(($asset->maintenanceUsageRatio() ?? 0) * 100, 1) }}%</td>
                        <td>{{ $asset->next_service_due_on?->format('d M Y') ?? '—' }}</td>
                        <td>
                            @can('maintenance_order.create')
                            <a href="{{ route('admin.maintenance-orders.create', ['work_centre_id' => $asset->id, 'order_type' => 'preventive']) }}" class="btn btn-sm btn-primary-light">Open PM</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">No assets due for maintenance.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div></div>
@endsection
