@extends('admin.layouts.app')
@section('title', $type === 'payable' ? 'Payable Ageing' : 'Receivable Ageing')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">{{ $type === 'payable' ? 'Payable' : 'Receivable' }} Ageing</h1>
        <x-admin.module-intro />
    </div>
    <a href="{{ route('admin.finance-reports.ageing-export', ['type' => $type, 'as_on_date' => $asOnDate]) }}" class="btn btn-primary-light btn-sm" id="btnExportAgeing">Export CSV</a>
</div>
<div class="card custom-card"><div class="card-body">
<div class="row g-2 mb-3">
    <div class="col-md-3">
        <select id="filterType" class="form-select">
            <option value="receivable" @selected($type === 'receivable')>Receivable</option>
            <option value="payable" @selected($type === 'payable')>Payable</option>
        </select>
    </div>
    <div class="col-md-3"><input type="date" id="filterAsOnDate" class="form-control" value="{{ $asOnDate }}"></div>
    <div class="col-md-2"><button type="button" class="btn btn-primary" id="btnLoadAgeing">Refresh</button></div>
</div>
<div class="table-responsive">
<table class="table table-bordered text-nowrap w-100" id="ageingTable">
    <thead><tr><th>Party</th><th>Outstanding</th><th>0–30</th><th>31–60</th><th>61–90</th><th>90+</th></tr></thead>
    <tbody></tbody>
</table>
</div>
<p class="text-muted fs-12 mb-0">Balances come from posted journal vouchers on the configured control account.</p>
</div></div>
@endsection
@push('scripts')
<script>
window.ageingDataUrl = @json(route('admin.finance-reports.ageing-data'));
window.ageingExportUrl = @json(route('admin.finance-reports.ageing-export'));
</script>
<script src="{{ asset('assets/admin/js/admin/finance-reports/ageing.js') }}"></script>
@endpush
