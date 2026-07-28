@extends('admin.layouts.app')
@section('title', 'Profit & Loss')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Profit &amp; Loss</h1>
        <x-admin.module-intro />
    </div>
</div>
<div class="card custom-card"><div class="card-body">
<form method="GET" action="{{ route('admin.finance-reports.profit-and-loss') }}" class="row g-2 mb-3">
    <div class="col-md-3"><input type="date" name="from_date" class="form-control" value="{{ $fromDate }}"></div>
    <div class="col-md-3"><input type="date" name="to_date" class="form-control" value="{{ $toDate }}"></div>
    <div class="col-md-2"><button class="btn btn-primary" type="submit">View</button></div>
</form>
<div class="row">
    <div class="col-md-6">
        <h6>Income</h6>
        <table class="table table-bordered">
            <thead><tr><th>Code</th><th>Account</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
            @forelse ($report['income'] as $row)
                <tr><td>{{ $row['code'] }}</td><td>{{ $row['name'] }}</td><td class="text-end">{{ number_format($row['amount'], 2) }}</td></tr>
            @empty
                <tr><td colspan="3" class="text-muted">No income for this period.</td></tr>
            @endforelse
            </tbody>
            <tfoot><tr><th colspan="2">Total Income</th><th class="text-end">{{ number_format($report['total_income'], 2) }}</th></tr></tfoot>
        </table>
    </div>
    <div class="col-md-6">
        <h6>Expenses</h6>
        <table class="table table-bordered">
            <thead><tr><th>Code</th><th>Account</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
            @forelse ($report['expense'] as $row)
                <tr><td>{{ $row['code'] }}</td><td>{{ $row['name'] }}</td><td class="text-end">{{ number_format($row['amount'], 2) }}</td></tr>
            @empty
                <tr><td colspan="3" class="text-muted">No expenses for this period.</td></tr>
            @endforelse
            </tbody>
            <tfoot><tr><th colspan="2">Total Expenses</th><th class="text-end">{{ number_format($report['total_expense'], 2) }}</th></tr></tfoot>
        </table>
    </div>
</div>
<div class="alert {{ $report['net_profit'] >= 0 ? 'alert-success' : 'alert-danger' }} mb-0">
    Net {{ $report['net_profit'] >= 0 ? 'Profit' : 'Loss' }}:
    <strong>{{ number_format(abs($report['net_profit']), 2) }}</strong>
</div>
</div></div>
@endsection
