@extends('admin.layouts.app')
@section('title', 'Stock Take '.$stockTake->document_no)
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">{{ $stockTake->document_no }} · {{ $stockTake->warehouse?->code }}</h1>
        <x-admin.module-intro />
    </div>
    <div class="d-flex gap-2">
        <form method="post" action="{{ route('admin.stock-takes.seed', $stockTake) }}" data-ajax="1" data-reload="1">@csrf<button class="btn btn-outline-secondary btn-sm" type="submit">Reseed</button></form>
        <form method="post" action="{{ route('admin.stock-takes.post', $stockTake) }}" data-ajax="1">@csrf<button class="btn btn-primary btn-sm" type="submit">Post Variances</button></form>
    </div>
</div>
<div class="card custom-card mb-3"><div class="card-body">
    <form method="post" action="{{ route('admin.stock-takes.scan', $stockTake) }}" data-ajax="1" data-reload="1" class="row g-2 align-items-end">
        @csrf
        <div class="col-md-8"><label class="form-label">Scan package</label><input type="text" name="code" class="form-control" required></div>
        <div class="col-md-4"><button type="submit" class="btn btn-secondary">Scan</button></div>
    </form>
</div></div>
<div class="card custom-card"><div class="card-body">
<form id="stockTakeLinesForm" data-ajax="1" data-reload="1" method="post" action="{{ route('admin.stock-takes.save-lines', $stockTake) }}">
    @csrf
    <div class="table-responsive">
        <table class="table table-bordered text-nowrap">
            <thead><tr><th>Item</th><th>Batch</th><th>System</th><th>Counted</th><th>Variance</th></tr></thead>
            <tbody>
            @foreach($stockTake->lines as $i => $line)
            <tr>
                <td>{{ $line->item?->item_code }}
                    <input type="hidden" name="lines[{{ $i }}][item_id]" value="{{ $line->item_id }}">
                    <input type="hidden" name="lines[{{ $i }}][batch_id]" value="{{ $line->batch_id }}">
                </td>
                <td>{{ $line->batch?->batch_no ?? '—' }}</td>
                <td>{{ $line->system_qty }}</td>
                <td><input type="number" step="0.0001" class="form-control" name="lines[{{ $i }}][counted_qty]" value="{{ $line->counted_qty }}"></td>
                <td>{{ $line->variance_qty }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <button type="submit" class="btn btn-primary">Save Counts</button>
</form>
</div></div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/gap-close/forms.js') }}"></script>
@endpush
