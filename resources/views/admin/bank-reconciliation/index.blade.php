@extends('admin.layouts.app')
@section('title', 'Bank Reconciliation')
@section('content')
<div class="my-4 page-header-breadcrumb"><h1 class="page-title fw-semibold fs-18 mb-0">Bank Reconciliation</h1></div>
<div class="card custom-card mb-3"><div class="card-body">
<form method="get" class="row g-2">
    <div class="col-md-4">
        <select name="ledger_account_id" class="form-select">
            @foreach($accounts as $account)
                <option value="{{ $account->id }}" @selected($accountId === $account->id)>{{ $account->code }} — {{ $account->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3"><input type="date" name="from_date" class="form-control" value="{{ $fromDate }}"></div>
    <div class="col-md-3"><input type="date" name="to_date" class="form-control" value="{{ $toDate }}"></div>
    <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Load</button></div>
</form>
</div></div>
<form data-ajax="1" data-reload="1" method="post" action="{{ route('admin.bank-reconciliation.reconcile') }}">
@csrf
<div class="card custom-card"><div class="card-body table-responsive">
<div class="mb-2 d-flex gap-2">
    <input type="date" name="bank_date" class="form-control w-auto" value="{{ now()->toDateString() }}">
    <button class="btn btn-success" type="submit">Reconcile Selected</button>
</div>
<table class="table table-bordered">
<thead><tr><th></th><th>Voucher</th><th>Date</th><th>Narration</th><th>Debit</th><th>Credit</th></tr></thead>
<tbody>
@forelse($lines as $line)
<tr>
    <td><input type="checkbox" name="line_ids[]" value="{{ $line->id }}"></td>
    <td>{{ $line->voucher?->document_no }}</td>
    <td>{{ $line->voucher?->document_date?->toDateString() }}</td>
    <td>{{ $line->narration }}</td>
    <td>{{ number_format((float)$line->debit, 2) }}</td>
    <td>{{ number_format((float)$line->credit, 2) }}</td>
</tr>
@empty
<tr><td colspan="6" class="text-muted">No unreconciled lines.</td></tr>
@endforelse
</tbody>
</table>
</div></div>
</form>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/gap-close/forms.js') }}"></script>
@endpush
