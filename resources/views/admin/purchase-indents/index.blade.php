@extends('admin.layouts.app')
@section('title', 'Purchase Indents')
@section('content')
<div class="my-4 page-header-breadcrumb d-flex justify-content-between align-items-center">
    <h1 class="page-title fw-semibold fs-18 mb-0">Purchase Indents</h1>
</div>
<div class="card custom-card mb-3"><div class="card-body">
<form data-ajax="1" data-reload="1" method="post" action="{{ route('admin.purchase-indents.store') }}">
    @csrf
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Warehouse</label>
            <select name="warehouse_id" class="form-select" required>
                <option value="">Select</option>
                @foreach($warehouses as $wh)
                    <option value="{{ $wh->id }}">{{ $wh->code }} — {{ $wh->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Remarks</label>
            <input name="remarks" class="form-control" placeholder="From reorder suggestions">
        </div>
        <div class="col-md-3"><button class="btn btn-primary w-100" type="submit">Create from Suggestions</button></div>
    </div>
</form>
</div></div>
<div class="card custom-card"><div class="card-body table-responsive">
<table class="table table-bordered">
<thead><tr><th>No</th><th>Date</th><th>Warehouse</th><th>Status</th><th>Lines</th><th></th></tr></thead>
<tbody>
@forelse($indents as $indent)
<tr>
    <td><a href="{{ route('admin.purchase-indents.show', $indent) }}">{{ $indent->document_no }}</a></td>
    <td>{{ $indent->document_date?->toDateString() }}</td>
    <td>{{ $indent->warehouse?->name }}</td>
    <td>{{ $indent->status->label() }}</td>
    <td>{{ $indent->items->count() }}</td>
    <td>
        @if($indent->status->value === 'draft')
        <form data-ajax="1" data-reload="1" method="post" action="{{ route('admin.purchase-indents.approve', $indent) }}" class="d-inline">@csrf<button class="btn btn-sm btn-success">Approve</button></form>
        @endif
    </td>
</tr>
@empty
<tr><td colspan="6" class="text-muted">No indents yet.</td></tr>
@endforelse
</tbody>
</table>
</div></div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/gap-close/forms.js') }}"></script>
@endpush
