@extends('admin.print.layout')

@section('title', 'Certificate of Analysis '.$inspection->document_no)

@section('content')
@php
    $company = \App\Models\Company::query()->first();
@endphp

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <div class="doc-title">Certificate of Analysis</div>
        <div>{{ $company?->legal_name ?? config('app.name', 'Kepler ERP') }}</div>
        @if ($company?->gstin)
            <div class="fs-12">GSTIN: {{ $company->gstin }}</div>
        @endif
    </div>
    <div class="text-end">
        <div><strong>{{ $inspection->document_no }}</strong></div>
        <div>{{ $inspection->document_date?->format('d M Y') }}</div>
        <div class="fs-12">{{ $inspection->inspection_type->label() }}</div>
    </div>
</div>

<table class="lines mb-3">
    <tbody>
        <tr>
            <th style="width:18%">Item</th>
            <td style="width:32%">{{ $inspection->item?->item_code }} — {{ $inspection->item?->item_name }}</td>
            <th style="width:18%">Batch</th>
            <td>{{ $inspection->batch?->batch_no ?? '—' }}
                @if ($inspection->batch?->expiry_date)
                    (Exp {{ $inspection->batch->expiry_date->format('d M Y') }})
                @endif
            </td>
        </tr>
        <tr>
            <th>Lot Qty</th>
            <td>{{ number_format((float) $inspection->lot_quantity, 4) }} {{ $inspection->item?->stockUom?->code }}</td>
            <th>Sample Size</th>
            <td>{{ number_format((float) $inspection->sample_size, 4) }}</td>
        </tr>
        <tr>
            <th>Overall Result</th>
            <td>{{ strtoupper((string) $inspection->overall_result) }}</td>
            <th>Disposition</th>
            <td>{{ $inspection->disposition?->label() ?? '—' }}</td>
        </tr>
        <tr>
            <th>Accepted</th>
            <td>{{ number_format((float) $inspection->accepted_qty, 4) }}</td>
            <th>Rejected</th>
            <td>{{ number_format((float) $inspection->rejected_qty, 4) }}</td>
        </tr>
        <tr>
            <th>Inspector</th>
            <td>{{ $inspection->inspector?->name ?? '—' }}</td>
            <th>Completed</th>
            <td>{{ $inspection->completed_at?->format('d M Y H:i') ?? '—' }}</td>
        </tr>
    </tbody>
</table>

<table class="lines">
    <thead>
        <tr>
            <th>#</th>
            <th>Parameter</th>
            <th>Spec</th>
            <th>Reading</th>
            <th>Result</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($inspection->readings as $index => $reading)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    {{ $reading->parameter_name }}
                    @if ($reading->is_critical)
                        <span class="fs-11">(Critical)</span>
                    @endif
                </td>
                <td>
                    @if ($reading->min_value !== null || $reading->max_value !== null)
                        {{ $reading->min_value ?? '—' }} – {{ $reading->max_value ?? '—' }}
                    @elseif ($reading->target_value !== null)
                        Target {{ $reading->target_value }}
                    @else
                        —
                    @endif
                </td>
                <td>
                    @if ($reading->numeric_value !== null)
                        {{ $reading->numeric_value }}
                    @elseif ($reading->pass_fail_value !== null)
                        {{ $reading->pass_fail_value }}
                    @else
                        {{ $reading->text_value ?? '—' }}
                    @endif
                </td>
                <td>{{ strtoupper((string) ($reading->result ?? '—')) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

@if ($inspection->deviation_note)
    <p class="mt-3"><strong>Deviation note:</strong> {{ $inspection->deviation_note }}</p>
@endif
@if ($inspection->remarks)
    <p class="mt-2"><strong>Remarks:</strong> {{ $inspection->remarks }}</p>
@endif

<p class="mt-4 fs-12 text-muted">
    This Certificate of Analysis is generated from the completed QC inspection record.
    Print from the browser; attach a wet signature if required by the customer.
</p>
@endsection
