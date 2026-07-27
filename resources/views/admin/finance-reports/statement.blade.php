@extends('admin.layouts.app')
@section('title', 'Account Statement')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Account Statement</h1>
</div>
<div class="card custom-card"><div class="card-body">
<form method="GET" action="{{ route('admin.finance-reports.statement') }}" class="row g-2 mb-3">
    <div class="col-md-4">
        <select name="ledger_account_id" class="form-select" required>
            <option value="">Select account</option>
            @foreach ($accounts as $account)
                <option value="{{ $account->id }}" @selected((int) $selectedAccountId === (int) $account->id)>{{ $account->code }} — {{ $account->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3"><input type="date" name="from_date" class="form-control" value="{{ $fromDate }}"></div>
    <div class="col-md-3"><input type="date" name="to_date" class="form-control" value="{{ $toDate }}"></div>
    <div class="col-md-2"><button class="btn btn-primary" type="submit">View</button></div>
</form>

@if ($statement)
<div class="row gy-2 mb-3">
    <div class="col-md-4"><span class="text-muted d-block fs-12">Account</span><strong>{{ $statement['account']->code }} — {{ $statement['account']->name }}</strong></div>
    <div class="col-md-4"><span class="text-muted d-block fs-12">Opening (Dr +)</span><strong>{{ number_format($statement['opening'], 2) }}</strong></div>
    <div class="col-md-4"><span class="text-muted d-block fs-12">Closing (Dr +)</span><strong>{{ number_format($statement['closing'], 2) }}</strong></div>
</div>
<div class="table-responsive">
<table class="table table-bordered text-nowrap w-100">
    <thead><tr><th>Date</th><th>Voucher</th><th>Type</th><th>Reference</th><th>Party</th><th>Narration</th><th>Debit</th><th>Credit</th><th>Balance</th></tr></thead>
    <tbody>
    @forelse ($statement['rows'] as $row)
        <tr>
            <td>{{ $row['document_date'] }}</td>
            <td>{{ $row['document_no'] }}</td>
            <td>{{ $row['voucher_type'] }}</td>
            <td>{{ $row['reference_no'] }}</td>
            <td>{{ $row['party'] }}</td>
            <td>{{ $row['narration'] }}</td>
            <td>{{ number_format($row['debit'], 2) }}</td>
            <td>{{ number_format($row['credit'], 2) }}</td>
            <td>{{ number_format(abs($row['balance']), 2) }} {{ strtoupper($row['balance_side']) === 'DEBIT' ? 'Dr' : 'Cr' }}</td>
        </tr>
    @empty
        <tr><td colspan="9" class="text-muted">No posted entries in this period.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
@else
<p class="text-muted mb-0">Select an account to view its statement.</p>
@endif
</div></div>
@endsection
