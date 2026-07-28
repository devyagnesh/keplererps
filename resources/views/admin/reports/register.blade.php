@extends('admin.layouts.app')

@section('title', $definition['title'])

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">{{ $definition['title'] }}</h1>
        <x-admin.module-intro :module="'reports.'.$register" />
    </div>
    <a href="#" id="btnExport" class="btn btn-primary-light btn-sm">Export CSV</a>
</div>

<div class="card custom-card">
<div class="card-body">
<form id="registerFilters" class="row g-2 align-items-end">
    <div class="col-md-2">
        <label class="form-label">From</label>
        <input type="date" class="form-control" name="from_date" value="{{ $fromDate }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">To</label>
        <input type="date" class="form-control" name="to_date" value="{{ $toDate }}">
    </div>

    @isset($lookups['parties'])
    <div class="col-md-3">
        <label class="form-label">{{ $lookups['party_label'] }}</label>
        <select class="form-select" name="party_id">
            <option value="">All</option>
            @foreach ($lookups['parties'] as $party)
                <option value="{{ $party->id }}">{{ $party->party_code }} — {{ $party->party_name }}</option>
            @endforeach
        </select>
    </div>
    @endisset

    @isset($lookups['items'])
    <div class="col-md-3">
        <label class="form-label">Item</label>
        <select class="form-select" name="item_id">
            <option value="">All</option>
            @foreach ($lookups['items'] as $item)
                <option value="{{ $item->id }}">{{ $item->item_code }} — {{ $item->item_name }}</option>
            @endforeach
        </select>
    </div>
    @endisset

    @isset($lookups['warehouses'])
    <div class="col-md-3">
        <label class="form-label">Warehouse</label>
        <select class="form-select" name="warehouse_id">
            <option value="">All</option>
            @foreach ($lookups['warehouses'] as $warehouse)
                <option value="{{ $warehouse->id }}">{{ $warehouse->code }} — {{ $warehouse->name }}</option>
            @endforeach
        </select>
    </div>
    @endisset

    @isset($lookups['workCentres'])
    <div class="col-md-3">
        <label class="form-label">Work Centre</label>
        <select class="form-select" name="work_centre_id">
            <option value="">All</option>
            @foreach ($lookups['workCentres'] as $workCentre)
                <option value="{{ $workCentre->id }}">{{ $workCentre->code }} — {{ $workCentre->name }}</option>
            @endforeach
        </select>
    </div>
    @endisset

    @isset($lookups['statuses'])
    <div class="col-md-2">
        <label class="form-label">Status</label>
        <select class="form-select" name="status">
            <option value="">All</option>
            @foreach ($lookups['statuses'] as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>
    @endisset

    <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">Load</button>
    </div>
</form>
</div>
</div>

<div class="card custom-card">
<div class="card-body">
<div class="alert alert-warning d-none" id="truncatedNotice">
    Only the first {{ number_format(\App\Services\RegisterReportService::MAX_ROWS) }} rows are shown. Narrow the date range or add a filter.
</div>
<div class="table-responsive">
<table id="registerTable" class="table table-bordered text-nowrap w-100">
    <thead>
        <tr>
            @foreach ($definition['columns'] as $key => $label)
                <th class="{{ in_array($key, $definition['numeric'], true) ? 'text-end' : '' }}">{{ $label }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody></tbody>
    <tfoot>
        <tr class="fw-semibold">
            @foreach ($definition['columns'] as $key => $label)
                <th class="{{ in_array($key, $definition['numeric'], true) ? 'text-end' : '' }}" data-total="{{ $key }}">
                    {{ $loop->first ? 'Total' : '' }}
                </th>
            @endforeach
        </tr>
    </tfoot>
</table>
</div>
</div>
</div>
@endsection

@push('scripts')
<script>
    window.registerConfig = {
        dataUrl: @json(route('admin.reports.data', $register)),
        exportUrl: @json(route('admin.reports.export', $register)),
        columns: @json(array_keys($definition['columns'])),
        numeric: @json($definition['numeric'])
    };
</script>
<script src="{{ asset('assets/admin/js/admin/reports/register.js') }}"></script>
@endpush
