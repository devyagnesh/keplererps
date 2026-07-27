@extends('admin.layouts.app')
@section('title', 'Operator Board')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Operator Board</h1>
    <a href="{{ route('admin.shop-floor.capacity') }}" class="btn btn-light btn-sm">Capacity Chart</a>
</div>
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4">
        <select name="work_centre_id" class="form-select" onchange="this.form.submit()">
            <option value="">All work centres</option>
            @foreach ($workCentres as $centre)
                <option value="{{ $centre->id }}" @selected($selectedWorkCentreId === $centre->id)>{{ $centre->code }} — {{ $centre->name }}</option>
            @endforeach
        </select>
    </div>
</form>
<div class="card custom-card">
    <div class="table-responsive">
        <table class="table table-bordered mb-0">
            <thead>
                <tr>
                    <th>WO #</th>
                    <th>Item</th>
                    <th>Work Centre</th>
                    <th>Status</th>
                    <th>Planned Qty</th>
                    <th>Good Qty</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($workOrders as $wo)
                    <tr>
                        <td>{{ $wo->document_no }}</td>
                        <td>{{ $wo->item?->item_code }} — {{ $wo->item?->item_name }}</td>
                        <td>{{ $wo->workCentre?->code }}</td>
                        <td>{{ $wo->status->label() }}</td>
                        <td>{{ number_format((float) $wo->planned_quantity, 2) }}</td>
                        <td>{{ number_format((float) $wo->good_quantity, 2) }}</td>
                        <td><a href="{{ route('admin.shop-floor.costing', $wo) }}" class="btn btn-sm btn-outline-primary">Costing</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-muted text-center">No open work orders.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
