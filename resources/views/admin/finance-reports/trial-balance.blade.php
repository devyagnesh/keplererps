@extends('admin.layouts.app')
@section('title', 'Trial Balance')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Trial Balance</h1>
        <x-admin.module-intro />
    </div>
</div>
<div class="card custom-card"><div class="card-body">
<form method="GET" action="{{ route('admin.finance-reports.trial-balance') }}" class="row g-2 mb-3">
    <div class="col-md-3"><input type="date" name="from_date" class="form-control" value="{{ $fromDate }}"></div>
    <div class="col-md-3"><input type="date" name="to_date" class="form-control" value="{{ $toDate }}"></div>
    <div class="col-md-2"><button class="btn btn-primary" type="submit">View</button></div>
</form>
<div class="table-responsive">
<table class="table table-bordered text-nowrap w-100">
    <thead><tr><th>Code</th><th>Account</th><th>Type</th><th>Debit Movement</th><th>Credit Movement</th><th>Closing Dr</th><th>Closing Cr</th></tr></thead>
    <tbody>
    @forelse ($report['rows'] as $row)
        <tr>
            <td>{{ $row['code'] }}</td>
            <td>{{ $row['name'] }}</td>
            <td>{{ $row['account_type'] }}</td>
            <td>{{ number_format($row['debit_movement'], 2) }}</td>
            <td>{{ number_format($row['credit_movement'], 2) }}</td>
            <td>{{ number_format($row['closing_debit'], 2) }}</td>
            <td>{{ number_format($row['closing_credit'], 2) }}</td>
        </tr>
    @empty
        <tr><td colspan="7" class="text-muted">No balances for this period.</td></tr>
    @endforelse
    </tbody>
    <tfoot><tr>
        <th colspan="5" class="text-end">Totals</th>
        <th>{{ number_format($report['total_debit'], 2) }}</th>
        <th>{{ number_format($report['total_credit'], 2) }}</th>
    </tr></tfoot>
</table>
</div>
</div></div>
@endsection
