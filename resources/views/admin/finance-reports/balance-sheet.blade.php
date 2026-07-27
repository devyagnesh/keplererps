@extends('admin.layouts.app')
@section('title', 'Balance Sheet')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Balance Sheet</h1>
</div>
<div class="card custom-card"><div class="card-body">
<form method="GET" action="{{ route('admin.finance-reports.balance-sheet') }}" class="row g-2 mb-3">
    <div class="col-md-3"><input type="date" name="as_on_date" class="form-control" value="{{ $asOnDate }}"></div>
    <div class="col-md-2"><button class="btn btn-primary" type="submit">View</button></div>
</form>
<div class="row">
    <div class="col-md-6">
        <h6>Assets</h6>
        <table class="table table-bordered">
            <thead><tr><th>Code</th><th>Account</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
            @forelse ($report['assets'] as $row)
                <tr><td>{{ $row['code'] }}</td><td>{{ $row['name'] }}</td><td class="text-end">{{ number_format($row['amount'], 2) }}</td></tr>
            @empty
                <tr><td colspan="3" class="text-muted">No assets.</td></tr>
            @endforelse
            </tbody>
            <tfoot><tr><th colspan="2">Total Assets</th><th class="text-end">{{ number_format($report['total_assets'], 2) }}</th></tr></tfoot>
        </table>
    </div>
    <div class="col-md-6">
        <h6>Liabilities</h6>
        <table class="table table-bordered mb-3">
            <thead><tr><th>Code</th><th>Account</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
            @forelse ($report['liabilities'] as $row)
                <tr><td>{{ $row['code'] }}</td><td>{{ $row['name'] }}</td><td class="text-end">{{ number_format($row['amount'], 2) }}</td></tr>
            @empty
                <tr><td colspan="3" class="text-muted">No liabilities.</td></tr>
            @endforelse
            </tbody>
            <tfoot><tr><th colspan="2">Total Liabilities</th><th class="text-end">{{ number_format($report['total_liabilities'], 2) }}</th></tr></tfoot>
        </table>
        <h6>Equity</h6>
        <table class="table table-bordered">
            <thead><tr><th>Code</th><th>Account</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
            @forelse ($report['equity'] as $row)
                <tr><td>{{ $row['code'] }}</td><td>{{ $row['name'] }}</td><td class="text-end">{{ number_format($row['amount'], 2) }}</td></tr>
            @empty
                <tr><td colspan="3" class="text-muted">No equity.</td></tr>
            @endforelse
            </tbody>
            <tfoot><tr><th colspan="2">Total Equity</th><th class="text-end">{{ number_format($report['total_equity'], 2) }}</th></tr></tfoot>
        </table>
    </div>
</div>
<div class="alert alert-light mb-0">
    Liabilities + Equity = <strong>{{ number_format($report['total_liabilities'] + $report['total_equity'], 2) }}</strong>
    · Assets = <strong>{{ number_format($report['total_assets'], 2) }}</strong>
</div>
</div></div>
@endsection
