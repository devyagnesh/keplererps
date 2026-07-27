@extends('admin.layouts.app')
@section('title', 'Pack & Label')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Pack &amp; Label</h1>
    @if ($challan)
    <a href="{{ route('admin.packages.print', ['delivery_challan_id' => $challan->id]) }}" target="_blank" class="btn btn-primary-light btn-sm">Print All Labels</a>
    @endif
</div>

<div class="card custom-card"><div class="card-body">
<form method="GET" action="{{ route('admin.packages.pack') }}" class="row g-2">
    <div class="col-md-5">
        <select name="delivery_challan_id" class="form-select" required>
            <option value="">Select a delivery challan</option>
            @foreach ($challans as $option)
                <option value="{{ $option->id }}" @selected($challan && (int) $challan->id === (int) $option->id)>
                    {{ $option->document_no }} · {{ $option->document_date?->format('d M Y') }} · {{ $option->status->label() }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-primary" type="submit">Load</button></div>
</form>
</div></div>

@if ($challan)
<div class="card custom-card">
<div class="card-header"><div class="card-title">{{ $challan->document_no }} — {{ $challan->customer?->party_name }}</div></div>
<div class="card-body table-responsive">
<table class="table table-bordered text-nowrap w-100">
    <thead><tr><th>Item</th><th>Batch</th><th>Challan Qty</th><th>Packed</th><th>Open</th><th>Packages</th><th>Pack</th></tr></thead>
    <tbody>
    @foreach ($summary as $row)
        <tr>
            <td>{{ $row['item_code'] }} — {{ $row['item_name'] }}</td>
            <td>{{ $row['batch_no'] ?? '—' }}</td>
            <td>{{ number_format($row['challan_qty'], 4) }}</td>
            <td>{{ number_format($row['packed_qty'], 4) }}</td>
            <td>{{ number_format($row['open_qty'], 4) }}</td>
            <td>{{ $row['package_count'] }} ({{ $row['verified_count'] }} scanned)</td>
            <td>
                @can('package.create')
                    @if ($row['open_qty'] > 0)
                    <button type="button" class="btn btn-sm btn-primary btn-open-pack"
                        data-line-id="{{ $row['delivery_challan_item_id'] }}"
                        data-item="{{ $row['item_code'] }}"
                        data-open-qty="{{ $row['open_qty'] }}">Print Labels</button>
                    @else
                    <span class="text-muted fs-12">Fully packed</span>
                    @endif
                @endcan
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div></div>

<div class="card custom-card">
<div class="card-header"><div class="card-title">Printed Labels</div></div>
<div class="card-body table-responsive">
<table class="table table-bordered text-nowrap w-100">
    <thead><tr><th>Label No</th><th>Item</th><th>Packing Unit</th><th>Qty</th><th>Status</th><th>QR Payload</th></tr></thead>
    <tbody>
    @forelse ($packages as $package)
        <tr>
            <td>{{ $package->label_no }}</td>
            <td>{{ $package->item?->item_code }}</td>
            <td>{{ $package->packingUnit?->code }}</td>
            <td>{{ number_format((float) $package->quantity, 4) }}</td>
            <td><span class="badge {{ $package->status->badgeClass() }}">{{ $package->status->label() }}</span></td>
            <td><code>{{ $package->qr_payload }}</code></td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-muted">No labels printed for this challan yet.</td></tr>
    @endforelse
    </tbody>
</table>
</div></div>

<div class="modal fade" id="packModal" tabindex="-1" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">
<div class="modal-header"><h6 class="modal-title">Print Package Labels</h6>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
<form id="packForm" action="{{ route('admin.packages.store') }}" method="POST" novalidate>
@csrf
<input type="hidden" name="delivery_challan_id" value="{{ $challan->id }}">
<input type="hidden" name="delivery_challan_item_id" id="packLineId">
<div class="modal-body row gy-3">
    <div class="col-12">
        <p class="text-muted mb-0 fs-12">Packing <strong id="packItemLabel"></strong> · open quantity <strong id="packOpenQty"></strong></p>
    </div>
    <div class="col-md-6"><label class="form-label">Packing Unit *</label>
        <select class="form-select" name="packing_unit_id" id="packUnit" required>
            <option value="">Select packing unit</option>
            @foreach ($packingUnits as $unit)
                <option value="{{ $unit->id }}" data-base-qty="{{ $unit->baseQuantity() }}">{{ $unit->code }} — {{ $unit->name }} ({{ number_format($unit->baseQuantity(), 4) }} {{ $unit->uom?->code }})</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3"><label class="form-label">Qty / Package</label>
        <input type="number" step="0.0001" min="0.0001" class="form-control" name="quantity_per_package" id="packQtyPerPackage">
    </div>
    <div class="col-md-3"><label class="form-label">Labels *</label>
        <input type="number" min="1" max="500" class="form-control" name="package_count" id="packCount" value="1" required>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
    <button type="submit" class="btn btn-primary">Create Labels</button>
</div>
</form>
</div></div></div>
@endif
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/packages/pack.js') }}"></script>
@endpush
