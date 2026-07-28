@extends('admin.layouts.app')
@section('title', 'QC Inspection '.$inspection->document_no)
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">{{ $inspection->document_no }}</h1>
        <x-admin.module-intro />
        <div class="text-muted fs-12">{{ $inspection->status->label() }} · {{ $inspection->inspection_type->label() }}</div>
    </div>
    <div class="hstack gap-2">
        @if ($inspection->status === \App\Enums\InspectionStatus::Completed)
            <a href="{{ route('admin.qc-inspections.coa', $inspection) }}" class="btn btn-primary btn-sm" target="_blank">Print CoA</a>
        @endif
        <a href="{{ route('admin.qc-inspections.index') }}" class="btn btn-light btn-sm">Back</a>
    </div>
</div>

<div class="card custom-card mb-3"><div class="card-body">
    <div class="row g-3">
        <div class="col-md-3"><div class="text-muted fs-12">Item</div><div>{{ $inspection->item?->item_code }} — {{ $inspection->item?->item_name }}</div></div>
        <div class="col-md-2"><div class="text-muted fs-12">Lot qty</div><div>{{ number_format((float) $inspection->lot_quantity, 4) }} {{ $inspection->item?->stockUom?->code }}</div></div>
        <div class="col-md-2"><div class="text-muted fs-12">Sample size</div><div>{{ number_format((float) $inspection->sample_size, 4) }}</div></div>
        <div class="col-md-2"><div class="text-muted fs-12">Quarantine</div><div>{{ $inspection->quarantineWarehouse?->code ?? '—' }}</div></div>
        <div class="col-md-3"><div class="text-muted fs-12">Target store</div><div>{{ $inspection->targetWarehouse?->code ?? '—' }}</div></div>
    </div>
</div></div>

@php $editable = $inspection->status->isEditable(); @endphp
<form id="inspectionForm"
      action="{{ route('admin.qc-inspections.update', $inspection) }}"
      method="POST"
      novalidate
      data-complete-url="{{ route('admin.qc-inspections.complete', $inspection) }}"
      data-editable="{{ $editable ? '1' : '0' }}">
@csrf
<input type="hidden" name="_method" value="PUT">

<div class="card custom-card mb-3"><div class="card-body">
    <h6 class="mb-3">Readings</h6>
    @forelse ($inspection->readings as $index => $reading)
        <div class="row g-2 mb-3 reading-row align-items-end">
            <input type="hidden" name="readings[{{ $index }}][id]" value="{{ $reading->id }}">
            <div class="col-md-3">
                <label class="form-label mb-0">{{ $reading->parameter_name }} @if($reading->is_critical)<span class="badge bg-danger-transparent">Critical</span>@endif</label>
                <div class="fs-12 text-muted">{{ $reading->parameter_type->label() }}
                    @if ($reading->min_value !== null || $reading->max_value !== null)
                        ({{ $reading->min_value }} – {{ $reading->max_value }})
                    @endif
                </div>
            </div>
            @if ($reading->parameter_type->value === 'numeric')
                <div class="col-md-3">
                    <input type="number" step="any" class="form-control" name="readings[{{ $index }}][numeric_value]" value="{{ old('readings.'.$index.'.numeric_value', $reading->numeric_value) }}" {{ $editable ? '' : 'disabled' }}>
                </div>
            @elseif ($reading->parameter_type->value === 'pass_fail')
                <div class="col-md-3">
                    <select class="form-select" name="readings[{{ $index }}][pass_fail_value]" {{ $editable ? '' : 'disabled' }}>
                        <option value="">Select</option>
                        <option value="pass" @selected(old('readings.'.$index.'.pass_fail_value', $reading->pass_fail_value) === 'pass')>Pass</option>
                        <option value="fail" @selected(old('readings.'.$index.'.pass_fail_value', $reading->pass_fail_value) === 'fail')>Fail</option>
                    </select>
                </div>
            @else
                <div class="col-md-3">
                    <input type="text" class="form-control" name="readings[{{ $index }}][text_value]" value="{{ old('readings.'.$index.'.text_value', $reading->text_value) }}" {{ $editable ? '' : 'disabled' }}>
                </div>
            @endif
            <div class="col-md-2">
                <span class="badge {{ $reading->result === 'fail' ? 'bg-danger' : ($reading->result === 'pass' ? 'bg-success' : 'bg-secondary') }}">
                    {{ $reading->result ? strtoupper($reading->result) : 'PENDING' }}
                </span>
            </div>
        </div>
    @empty
        <div class="text-muted">No parameters on this inspection. Assign an active QC template for the item/stage.</div>
    @endforelse
</div></div>

<div class="card custom-card mb-3"><div class="card-body">
    <h6 class="mb-3">Disposition</h6>
    <div class="row g-3">
        <div class="col-md-2">
            <label class="form-label">Accepted qty</label>
            <input type="number" step="0.0001" min="0" class="form-control" name="accepted_qty" value="{{ old('accepted_qty', $inspection->accepted_qty) }}" {{ $editable ? '' : 'disabled' }}>
        </div>
        <div class="col-md-2">
            <label class="form-label">Rejected qty</label>
            <input type="number" step="0.0001" min="0" class="form-control" name="rejected_qty" value="{{ old('rejected_qty', $inspection->rejected_qty) }}" {{ $editable ? '' : 'disabled' }}>
        </div>
        <div class="col-md-2">
            <label class="form-label">Rework qty</label>
            <input type="number" step="0.0001" min="0" class="form-control" name="rework_qty" value="{{ old('rework_qty', $inspection->rework_qty) }}" {{ $editable ? '' : 'disabled' }}>
        </div>
        <div class="col-md-3">
            <label class="form-label">Disposition</label>
            <select name="disposition" class="form-select" {{ $editable ? '' : 'disabled' }}>
                <option value="">Select</option>
                @foreach ($dispositions as $disposition)
                    <option value="{{ $disposition->value }}" @selected(old('disposition', $inspection->disposition?->value) === $disposition->value)>{{ $disposition->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Deviation note</label>
            <input type="text" class="form-control" name="deviation_note" value="{{ old('deviation_note', $inspection->deviation_note) }}" {{ $editable ? '' : 'disabled' }}>
        </div>
        <div class="col-12">
            <label class="form-label">Remarks</label>
            <textarea class="form-control" name="remarks" rows="2" {{ $editable ? '' : 'disabled' }}>{{ old('remarks', $inspection->remarks) }}</textarea>
        </div>
    </div>
</div></div>

@if ($editable)
<div class="mb-4">
    <button type="submit" class="btn btn-primary" id="btnSaveInspection">Save readings</button>
    <button type="button" class="btn btn-success" id="btnCompleteInspection">Complete inspection</button>
</div>
@endif
</form>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/qc-inspections/form.js') }}"></script>
@endpush
