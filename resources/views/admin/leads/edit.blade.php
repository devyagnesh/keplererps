@extends('admin.layouts.app')
@section('title', 'Lead '.$lead->lead_no)
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Lead {{ $lead->lead_no }}</h1>
        <x-admin.module-intro />
        <p class="text-muted mb-0">
            <span class="badge {{ $lead->status->badgeClass() }}">{{ $lead->status->label() }}</span>
            {{ $lead->source->label() }}
            @if ($lead->convertedParty)· customer {{ $lead->convertedParty->party_code }} @endif
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if ($lead->status->isOpen())
            @can('lead.update')
            <button type="button" class="btn btn-primary-light btn-sm btn-lead-status" data-url="{{ route('admin.leads.status', $lead) }}" data-status="qualified">Mark Qualified</button>
            <button type="button" class="btn btn-danger-light btn-sm btn-lead-lost" data-url="{{ route('admin.leads.status', $lead) }}">Mark Lost</button>
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#convertModal">Convert to Customer</button>
            @endcan
        @endif
        <a href="{{ route('admin.leads.index') }}" class="btn btn-light btn-sm">Back</a>
    </div>
</div>

@include('admin.leads._form', ['action' => route('admin.leads.update', $lead), 'method' => 'PUT'])

@if ($lead->status === \App\Enums\LeadStatus::Lost && $lead->lost_reason)
<div class="card custom-card"><div class="card-body">
    <span class="text-muted d-block fs-12">Lost Reason</span>
    <strong>{{ $lead->lost_reason }}</strong>
</div></div>
@endif

@include('admin.partials.crm-follow-ups', [
    'owner' => $lead,
    'followUpUrl' => route('admin.leads.follow-up', $lead),
    'canLog' => $lead->status->isOpen() && auth()->user()?->hasPermissionTo('lead.update'),
])

@if ($lead->opportunities->isNotEmpty())
<div class="card custom-card">
<div class="card-header"><div class="card-title">Opportunities</div></div>
<div class="card-body table-responsive">
<table class="table table-bordered text-nowrap w-100">
    <thead><tr><th>Opportunity No</th><th>Stage</th><th>Expected Value</th><th></th></tr></thead>
    <tbody>
    @foreach ($lead->opportunities as $opportunity)
        <tr>
            <td>{{ $opportunity->opportunity_no }}</td>
            <td><span class="badge {{ $opportunity->stage->badgeClass() }}">{{ $opportunity->stage->label() }}</span></td>
            <td>{{ number_format((float) $opportunity->expected_value, 2) }}</td>
            <td>@can('opportunity.view')<a href="{{ route('admin.opportunities.edit', $opportunity) }}" class="btn btn-sm btn-primary-light">Open</a>@endcan</td>
        </tr>
    @endforeach
    </tbody>
</table>
</div></div>
@endif

@if ($lead->status->isOpen())
<div class="modal fade" id="convertModal" tabindex="-1" aria-hidden="true">
<div class="modal-dialog modal-lg modal-dialog-centered">
<div class="modal-content">
<div class="modal-header"><h6 class="modal-title">Convert {{ $lead->company_name }} to Customer</h6>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
<form id="convertForm" action="{{ route('admin.leads.convert', $lead) }}" method="POST" novalidate>
@csrf
<div class="modal-body row gy-3">
    <div class="col-md-4"><label class="form-label">GST Type *</label>
        <select class="form-select" name="gst_type" required>
            @foreach (\App\Enums\GstType::cases() as $type)
                <option value="{{ $type->value }}" @selected($type->value === 'unregistered')>{{ ucwords(str_replace('_', ' ', $type->value)) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4"><label class="form-label">GSTIN</label><input type="text" class="form-control" name="gstin" maxlength="15"></div>
    <div class="col-md-4"><label class="form-label">PAN</label><input type="text" class="form-control" name="pan" maxlength="10"></div>
    <div class="col-md-6"><label class="form-label">Billing Address Line 1 *</label><input type="text" class="form-control" name="billing_line1" required></div>
    <div class="col-md-6"><label class="form-label">Line 2</label><input type="text" class="form-control" name="billing_line2"></div>
    <div class="col-md-4"><label class="form-label">City *</label><input type="text" class="form-control" name="billing_city" value="{{ $lead->city }}" required></div>
    <div class="col-md-4"><label class="form-label">State *</label>
        <select class="form-select" name="billing_state_id" required>
            <option value="">Select state</option>
            @foreach ($states as $state)
                <option value="{{ $state->id }}" @selected((int) $lead->state_id === (int) $state->id)>{{ $state->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4"><label class="form-label">PIN Code *</label><input type="text" class="form-control" name="billing_pin_code" maxlength="6" required></div>
    <div class="col-md-4"><label class="form-label">Credit Limit</label><input type="number" step="0.01" min="0" class="form-control" name="credit_limit" value="0"></div>
    <div class="col-md-4"><label class="form-label">Credit Days</label><input type="number" min="0" max="365" class="form-control" name="credit_days"></div>
    <div class="col-12"><hr class="my-1"></div>
    <div class="col-md-4 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="create_opportunity" value="1" id="createOpportunity" checked>
            <label class="form-check-label" for="createOpportunity">Also open an opportunity</label>
        </div>
    </div>
    <div class="col-md-4"><label class="form-label">Opportunity Title</label>
        <input type="text" class="form-control" name="opportunity_title" value="Opportunity for {{ $lead->company_name }}">
    </div>
    <div class="col-md-4"><label class="form-label">Expected Close Date</label><input type="date" class="form-control" name="expected_close_date"></div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
    <button type="submit" class="btn btn-success">Convert</button>
</div>
</form>
</div></div></div>
@endif
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/leads/form.js') }}"></script>
@endpush
