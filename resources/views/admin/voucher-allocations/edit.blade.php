@extends('admin.layouts.app')
@section('title', 'Allocate Voucher')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div><h1 class="page-title fw-semibold fs-18 mb-0">Allocate {{ $voucher->document_no }}</h1>
        <x-admin.module-intro />
    </div>
    <a href="{{ route('admin.journal-vouchers.edit', $voucher) }}" class="btn btn-outline-secondary btn-sm">Back</a>
</div>
<div class="card custom-card"><div class="card-body">
    <p class="text-muted mb-3">Voucher total: <strong>{{ number_format((float) $voucher->total_debit, 2) }}</strong> · Status: {{ $voucher->status->label() }}</p>
    <form id="allocationForm" method="post" action="{{ route('admin.voucher-allocations.sync', $voucher) }}">
        @csrf
        <div class="table-responsive mb-3">
            <table class="table table-bordered" id="allocTable">
                <thead><tr><th>Document</th><th>Allocate</th><th>Remarks</th></tr></thead>
                <tbody>
                @forelse($allocations as $i => $row)
                    <tr>
                        <td>{{ class_basename($row->allocatable_type) }} #{{ $row->allocatable_id }}
                            <input type="hidden" name="allocations[{{ $i }}][allocatable_type]" value="{{ $row->allocatable_type }}">
                            <input type="hidden" name="allocations[{{ $i }}][allocatable_id]" value="{{ $row->allocatable_id }}">
                        </td>
                        <td><input type="number" step="0.01" min="0" class="form-control" name="allocations[{{ $i }}][amount]" value="{{ $row->amount }}"></td>
                        <td><input type="text" class="form-control" name="allocations[{{ $i }}][remarks]" value="{{ $row->remarks }}"></td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-muted">No allocations yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <button type="submit" class="btn btn-primary">Save Allocations</button>
    </form>
</div></div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/gap-close/forms.js') }}"></script>
@endpush
