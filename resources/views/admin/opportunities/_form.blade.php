@php $readonly = $opportunity && ! $opportunity->stage->isOpen(); @endphp
<div class="card custom-card"><div class="card-body">
<form id="opportunityForm" action="{{ $action }}" method="POST" novalidate>
@csrf
<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3">
    <div class="col-md-3"><label class="form-label">Date *</label>
        <input type="date" class="form-control" name="opportunity_date" value="{{ old('opportunity_date', optional($opportunity?->opportunity_date)->format('Y-m-d') ?? now()->toDateString()) }}" {{ $readonly ? 'readonly' : '' }} required>
    </div>
    <div class="col-md-5"><label class="form-label">Title *</label>
        <input type="text" class="form-control" name="title" value="{{ old('title', $opportunity?->title) }}" {{ $readonly ? 'readonly' : '' }} required>
    </div>
    <div class="col-md-4"><label class="form-label">Owner</label>
        <select class="form-select" name="assigned_user_id" {{ $readonly ? 'disabled' : '' }}>
            <option value="">—</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected((string) old('assigned_user_id', $opportunity?->assigned_user_id) === (string) $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4"><label class="form-label">Customer</label>
        <select class="form-select" name="party_id" {{ $readonly ? 'disabled' : '' }}>
            <option value="">—</option>
            @foreach ($parties as $party)
                <option value="{{ $party->id }}" @selected((string) old('party_id', $opportunity?->party_id) === (string) $party->id)>{{ $party->party_code }} — {{ $party->party_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4"><label class="form-label">Source Lead</label>
        <select class="form-select" name="lead_id" {{ $readonly ? 'disabled' : '' }}>
            <option value="">—</option>
            @foreach ($leads as $sourceLead)
                <option value="{{ $sourceLead->id }}" @selected((string) old('lead_id', $opportunity?->lead_id) === (string) $sourceLead->id)>{{ $sourceLead->lead_no }} — {{ $sourceLead->company_name }}</option>
            @endforeach
        </select>
        <small class="text-muted">Provide a customer or a lead.</small>
    </div>
    <div class="col-md-4"><label class="form-label">Expected Value</label>
        <input type="number" step="0.01" min="0" class="form-control" name="expected_value" value="{{ old('expected_value', $opportunity ? (float) $opportunity->expected_value : 0) }}" {{ $readonly ? 'readonly' : '' }}>
    </div>
    <div class="col-md-4"><label class="form-label">Win Probability (%)</label>
        <input type="number" min="0" max="100" class="form-control" name="probability_percent" value="{{ old('probability_percent', $opportunity?->probability_percent ?? 25) }}" {{ $readonly ? 'readonly' : '' }}>
    </div>
    <div class="col-md-4"><label class="form-label">Expected Close Date</label>
        <input type="date" class="form-control" name="expected_close_date" value="{{ old('expected_close_date', optional($opportunity?->expected_close_date)->format('Y-m-d')) }}" {{ $readonly ? 'readonly' : '' }}>
    </div>
    <div class="col-12"><label class="form-label">Remarks</label>
        <textarea class="form-control" name="remarks" rows="2" {{ $readonly ? 'readonly' : '' }}>{{ old('remarks', $opportunity?->remarks) }}</textarea>
    </div>
</div>
@unless ($readonly)
<div class="mt-3">
    <button class="btn btn-primary" type="submit">Save Opportunity</button>
    <a href="{{ route('admin.opportunities.index') }}" class="btn btn-light">Cancel</a>
</div>
@endunless
</form>
</div></div>
