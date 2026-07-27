@extends('admin.print.layout')
@section('title', $title.' '.$document->document_no)

@section('content')
@if(!empty($header_html))
<div class="mb-3">{!! $header_html !!}</div>{{-- print template header HTML from Settings (sanitized at save time) --}}
@endif
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <div class="fw-bold fs-6">{{ $company?->trade_name ?? $company?->legal_name ?? config('app.name') }}</div>
        @if ($company?->registered_address)
            <div>{{ $company->registered_address }}</div>
        @endif
        <div>
            {{ collect([$company?->pin_code, $company?->state?->name])->filter()->implode(' · ') }}
        </div>
        <div>
            {{ collect([
                $company?->phone ? 'Ph: '.$company->phone : null,
                $company?->email,
            ])->filter()->implode(' · ') }}
        </div>
        @if ($company?->gstin)
            <div>GSTIN: {{ $company->gstin }}</div>
        @endif
    </div>
    <div class="text-end">
        <div class="doc-title">{{ $title }}</div>
        <div>{{ $document->document_no }}</div>
    </div>
</div>

<div class="rule mb-3"></div>

<div class="row mb-3">
    <div class="col-6">
        <div class="fw-semibold mb-1">{{ $partyHeading }}</div>
        @if ($party)
            <div class="fw-semibold">{{ $party->party_name }}</div>
            <div>{{ collect([$party->billing_line1, $party->billing_line2])->filter()->implode(', ') }}</div>
            <div>{{ collect([$party->billing_city, $party->billingState?->name, $party->billing_pin_code])->filter()->implode(', ') }}</div>
            @if ($party->gstin)
                <div>GSTIN: {{ $party->gstin }}</div>
            @endif
        @else
            <div>—</div>
        @endif
    </div>
    <div class="col-6">
        <table>
            @foreach ($meta as $label => $value)
                <tr>
                    <td class="pe-2 text-muted">{{ $label }}</td>
                    <td class="fw-semibold">{{ $value }}</td>
                </tr>
            @endforeach
        </table>
    </div>
</div>

<table class="lines mb-3">
    <thead>
        <tr>
            <th style="width:28px">#</th>
            <th>Description</th>
            <th style="width:56px">HSN</th>
            <th class="text-end" style="width:70px">Qty</th>
            <th style="width:44px">UOM</th>
            <th class="text-end" style="width:70px">Rate</th>
            @if ($showTaxColumns)
                <th class="text-end" style="width:62px">Disc</th>
                <th class="text-end" style="width:74px">Taxable</th>
                <th class="text-end" style="width:48px">GST%</th>
                <th class="text-end" style="width:70px">Tax</th>
            @endif
            <th class="text-end" style="width:82px">Amount</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $row['description'] }}</td>
                <td>{{ $row['hsn'] ?? '—' }}</td>
                <td class="text-end">{{ number_format($row['quantity'], 3) }}</td>
                <td>{{ $row['uom'] ?? '—' }}</td>
                <td class="text-end">{{ number_format($row['rate'], 2) }}</td>
                @if ($showTaxColumns)
                    <td class="text-end">{{ $row['discount'] === null ? '—' : number_format($row['discount'], 2) }}</td>
                    <td class="text-end">{{ $row['taxable'] === null ? '—' : number_format($row['taxable'], 2) }}</td>
                    <td class="text-end">{{ $row['gst_rate'] === null ? '—' : number_format($row['gst_rate'], 2) }}</td>
                    <td class="text-end">{{ $row['tax'] === null ? '—' : number_format($row['tax'], 2) }}</td>
                @endif
                <td class="text-end">{{ number_format($row['total'], 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="{{ $showTaxColumns ? 11 : 7 }}">No lines on this document.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="row">
    <div class="col-7">
        <div class="fw-semibold">Amount in words</div>
        <div>{{ $amountInWords }}</div>
        @if ($document->remarks)
            <div class="mt-2"><span class="fw-semibold">Remarks:</span> {{ $document->remarks }}</div>
        @endif
    </div>
    <div class="col-5">
        <table>
            @foreach ($totals as $label => $amount)
                <tr>
                    <td class="pe-3">{{ $label }}</td>
                    <td class="text-end fw-semibold">{{ number_format((float) $amount, 2) }}</td>
                </tr>
            @endforeach
        </table>
    </div>
</div>

<div class="row mt-5">
    <div class="col-6">
        <div class="text-muted">Prepared by</div>
    </div>
    <div class="col-6 text-end">
        <div class="text-muted">For {{ $company?->trade_name ?? $company?->legal_name ?? config('app.name') }}</div>
        <div class="mt-4">Authorised Signatory</div>
    </div>
</div>
@if(!empty($footer_html))
<div class="mt-3">{!! $footer_html !!}</div>{{-- print template footer HTML from Settings --}}
@endif
@endsection
