@extends('admin.layouts.app')
@section('title', 'GST Worksheets')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">GST Worksheets</h1>
        <x-admin.module-intro />
    </div>
    <div class="d-flex flex-wrap gap-1">
        <a class="btn btn-primary-light btn-sm" href="{{ route('admin.gst-reports.export', ['worksheet' => 'outward', 'from_date' => $fromDate, 'to_date' => $toDate]) }}">Export GSTR-1</a>
        <a class="btn btn-primary-light btn-sm" href="{{ route('admin.gst-reports.export', ['worksheet' => 'inward', 'from_date' => $fromDate, 'to_date' => $toDate]) }}">Export Inward</a>
        <form data-ajax="1" data-reload="1" method="post" action="{{ route('admin.gst-reports.gsp-push') }}" class="d-inline">
            @csrf
            <input type="hidden" name="from_date" value="{{ $fromDate }}">
            <input type="hidden" name="to_date" value="{{ $toDate }}">
            <button class="btn btn-primary btn-sm" type="submit">Push to GSP</button>
        </form>
    </div>
</div>

<div class="card custom-card"><div class="card-body">
<form method="GET" action="{{ route('admin.gst-reports.index') }}" class="row g-2">
    <div class="col-md-3"><input type="date" name="from_date" class="form-control" value="{{ $fromDate }}"></div>
    <div class="col-md-3"><input type="date" name="to_date" class="form-control" value="{{ $toDate }}"></div>
    <div class="col-md-2"><button class="btn btn-primary" type="submit">View</button></div>
</form>
</div></div>

<div class="card custom-card">
<div class="card-header"><div class="card-title">GSTR-3B Summary</div></div>
<div class="card-body table-responsive">
<table class="table table-bordered text-nowrap w-100">
    <thead><tr><th>Section</th><th>Invoices</th><th>Taxable Value</th><th>CGST</th><th>SGST</th><th>IGST</th><th>Total Tax</th></tr></thead>
    <tbody>
        <tr>
            <td>Outward supplies</td>
            <td>{{ $summary['outward']['count'] }}</td>
            <td>{{ number_format($summary['outward']['taxable_value'], 2) }}</td>
            <td>{{ number_format($summary['outward']['cgst'], 2) }}</td>
            <td>{{ number_format($summary['outward']['sgst'], 2) }}</td>
            <td>{{ number_format($summary['outward']['igst'], 2) }}</td>
            <td>{{ number_format($summary['outward']['tax_total'], 2) }}</td>
        </tr>
        <tr>
            <td>Inward supplies (ITC)</td>
            <td>{{ $summary['inward']['count'] }}</td>
            <td>{{ number_format($summary['inward']['taxable_value'], 2) }}</td>
            <td>{{ number_format($summary['inward']['cgst'], 2) }}</td>
            <td>{{ number_format($summary['inward']['sgst'], 2) }}</td>
            <td>{{ number_format($summary['inward']['igst'], 2) }}</td>
            <td>{{ number_format($summary['inward']['tax_total'], 2) }}</td>
        </tr>
    </tbody>
    <tfoot><tr>
        <th colspan="3" class="text-end">Net payable</th>
        <th>{{ number_format($summary['net_payable']['cgst'], 2) }}</th>
        <th>{{ number_format($summary['net_payable']['sgst'], 2) }}</th>
        <th>{{ number_format($summary['net_payable']['igst'], 2) }}</th>
        <th>{{ number_format($summary['net_payable']['total'], 2) }}</th>
    </tr></tfoot>
</table>
<p class="text-muted fs-12 mb-0">B2B invoices: {{ $summary['b2b_count'] }} · B2C invoices: {{ $summary['b2c_count'] }}</p>
</div></div>

<div class="card custom-card">
<div class="card-header"><div class="card-title">GSTR-1 Outward Supplies</div></div>
<div class="card-body table-responsive">
<table class="table table-bordered text-nowrap w-100">
    <thead><tr><th>Section</th><th>GSTIN</th><th>Party</th><th>Invoice No</th><th>Date</th><th>Place of Supply</th><th>Taxable</th><th>CGST</th><th>SGST</th><th>IGST</th><th>Invoice Value</th></tr></thead>
    <tbody>
    @forelse ($outward as $row)
        <tr>
            <td>{{ $row['section'] }}</td>
            <td>{{ $row['gstin'] }}</td>
            <td>{{ $row['party_name'] }}</td>
            <td>{{ $row['invoice_no'] }}</td>
            <td>{{ $row['invoice_date'] }}</td>
            <td>{{ $row['place_of_supply'] }}</td>
            <td>{{ number_format($row['taxable_value'], 2) }}</td>
            <td>{{ number_format($row['cgst'], 2) }}</td>
            <td>{{ number_format($row['sgst'], 2) }}</td>
            <td>{{ number_format($row['igst'], 2) }}</td>
            <td>{{ number_format($row['invoice_value'], 2) }}</td>
        </tr>
    @empty
        <tr><td colspan="11" class="text-muted">No confirmed invoices in this period.</td></tr>
    @endforelse
    </tbody>
</table>
</div></div>

<div class="card custom-card">
<div class="card-header"><div class="card-title">Inward Supplies (Input Tax Credit)</div></div>
<div class="card-body table-responsive">
<table class="table table-bordered text-nowrap w-100">
    <thead><tr><th>GSTIN</th><th>Party</th><th>Supplier Bill</th><th>Bill Date</th><th>Document</th><th>Taxable</th><th>CGST</th><th>SGST</th><th>IGST</th><th>Bill Value</th></tr></thead>
    <tbody>
    @forelse ($inward as $row)
        <tr>
            <td>{{ $row['gstin'] }}</td>
            <td>{{ $row['party_name'] }}</td>
            <td>{{ $row['bill_no'] }}</td>
            <td>{{ $row['bill_date'] }}</td>
            <td>{{ $row['document_no'] }}</td>
            <td>{{ number_format($row['taxable_value'], 2) }}</td>
            <td>{{ number_format($row['cgst'], 2) }}</td>
            <td>{{ number_format($row['sgst'], 2) }}</td>
            <td>{{ number_format($row['igst'], 2) }}</td>
            <td>{{ number_format($row['bill_value'], 2) }}</td>
        </tr>
    @empty
        <tr><td colspan="10" class="text-muted">No approved purchase bills in this period.</td></tr>
    @endforelse
    </tbody>
</table>
</div></div>

@if(!empty($gspLogs) && $gspLogs->isNotEmpty())
<div class="card custom-card">
<div class="card-header"><div class="card-title">Recent GSP Filing Attempts</div></div>
<div class="card-body table-responsive">
<table class="table table-bordered text-nowrap w-100">
    <thead><tr><th>When</th><th>Return</th><th>Period</th><th>Rows</th><th>Status</th></tr></thead>
    <tbody>
    @foreach($gspLogs as $log)
        <tr>
            <td>{{ $log->created_at }}</td>
            <td>{{ $log->return_type }}</td>
            <td>{{ $log->period_from?->toDateString() }} → {{ $log->period_to?->toDateString() }}</td>
            <td>{{ $log->row_count }}</td>
            <td>{{ $log->status }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</div></div>
@endif
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/gap-close/forms.js') }}"></script>
@endpush
