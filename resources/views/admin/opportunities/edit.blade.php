@extends('admin.layouts.app')
@section('title', 'Opportunity '.$opportunity->opportunity_no)
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Opportunity {{ $opportunity->opportunity_no }}</h1>
        <p class="text-muted mb-0">
            <span class="badge {{ $opportunity->stage->badgeClass() }}">{{ $opportunity->stage->label() }}</span>
            {{ $opportunity->probability_percent }}% · weighted {{ number_format($opportunity->weightedValue(), 2) }}
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if ($opportunity->stage->isOpen())
            @can('opportunity.update')
            @foreach (\App\Enums\OpportunityStage::cases() as $stage)
                @continue(! $stage->isOpen() || $stage === $opportunity->stage)
                <button type="button" class="btn btn-primary-light btn-sm btn-opportunity-stage" data-url="{{ route('admin.opportunities.stage', $opportunity) }}" data-stage="{{ $stage->value }}">Move to {{ $stage->label() }}</button>
            @endforeach
            <button type="button" class="btn btn-success btn-sm btn-opportunity-stage" data-url="{{ route('admin.opportunities.stage', $opportunity) }}" data-stage="won">Mark Won</button>
            <button type="button" class="btn btn-danger-light btn-sm btn-opportunity-lost" data-url="{{ route('admin.opportunities.stage', $opportunity) }}">Mark Lost</button>
            @endcan
        @endif
        <a href="{{ route('admin.opportunities.index') }}" class="btn btn-light btn-sm">Back</a>
    </div>
</div>

@include('admin.opportunities._form', ['action' => route('admin.opportunities.update', $opportunity), 'method' => 'PUT'])

<div class="card custom-card">
<div class="card-header"><div class="card-title">Quotation</div></div>
<div class="card-body">
@if ($opportunity->quotation)
    <div class="row gy-2">
        <div class="col-md-4"><span class="text-muted d-block fs-12">Quotation No</span><strong>{{ $opportunity->quotation->document_no }}</strong></div>
        <div class="col-md-4"><span class="text-muted d-block fs-12">Value</span><strong>{{ number_format((float) $opportunity->quotation->grand_total, 2) }}</strong></div>
        <div class="col-md-4">
            @can('sales_quotation.view')
            <a href="{{ route('admin.sales-quotations.edit', $opportunity->quotation_id) }}" class="btn btn-sm btn-primary-light">Open Quotation</a>
            @endcan
        </div>
    </div>
@elseif ($opportunity->stage->isOpen() && auth()->user()?->hasPermissionTo('opportunity.update'))
    <form id="attachQuotationForm" action="{{ route('admin.opportunities.quotation', $opportunity) }}" method="POST" class="row gy-2" novalidate>
        @csrf
        <div class="col-md-6">
            <select class="form-select" name="quotation_id" required>
                <option value="">Select a quotation to link</option>
                @foreach ($quotations as $quotation)
                    <option value="{{ $quotation->id }}">{{ $quotation->document_no }} · {{ $quotation->document_date?->format('d M Y') }} · {{ number_format((float) $quotation->grand_total, 2) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3"><button class="btn btn-primary" type="submit">Link Quotation</button></div>
    </form>
    <p class="text-muted fs-12 mb-0 mt-2">Linking a quotation moves the opportunity to Proposal Sent.</p>
@else
    <p class="text-muted mb-0">No quotation linked.</p>
@endif
</div></div>

@if ($opportunity->stage === \App\Enums\OpportunityStage::Lost && $opportunity->lost_reason)
<div class="card custom-card"><div class="card-body">
    <span class="text-muted d-block fs-12">Lost Reason</span>
    <strong>{{ $opportunity->lost_reason }}</strong>
</div></div>
@endif

@include('admin.partials.crm-follow-ups', [
    'owner' => $opportunity,
    'followUpUrl' => route('admin.opportunities.follow-up', $opportunity),
    'canLog' => $opportunity->stage->isOpen() && auth()->user()?->hasPermissionTo('opportunity.update'),
])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/opportunities/form.js') }}"></script>
@endpush
