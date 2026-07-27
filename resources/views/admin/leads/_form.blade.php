@php $readonly = $lead && ! $lead->status->isOpen(); @endphp
<div class="card custom-card"><div class="card-body">
<form id="leadForm" action="{{ $action }}" method="POST" novalidate>
@csrf
<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3">
    <div class="col-md-3"><label class="form-label">Lead Date *</label>
        <input type="date" class="form-control" name="lead_date" value="{{ old('lead_date', optional($lead?->lead_date)->format('Y-m-d') ?? now()->toDateString()) }}" {{ $readonly ? 'readonly' : '' }} required>
    </div>
    <div class="col-md-5"><label class="form-label">Company Name *</label>
        <input type="text" class="form-control" name="company_name" value="{{ old('company_name', $lead?->company_name) }}" {{ $readonly ? 'readonly' : '' }} required>
    </div>
    <div class="col-md-4"><label class="form-label">Source *</label>
        <select class="form-select" name="source" {{ $readonly ? 'disabled' : '' }} required>
            @foreach (\App\Enums\LeadSource::cases() as $source)
                <option value="{{ $source->value }}" @selected(old('source', $lead?->source?->value) === $source->value)>{{ $source->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4"><label class="form-label">Contact Person *</label>
        <input type="text" class="form-control" name="contact_person" value="{{ old('contact_person', $lead?->contact_person) }}" {{ $readonly ? 'readonly' : '' }} required>
    </div>
    <div class="col-md-4"><label class="form-label">Mobile *</label>
        <input type="text" class="form-control" name="mobile" value="{{ old('mobile', $lead?->mobile) }}" {{ $readonly ? 'readonly' : '' }} required>
    </div>
    <div class="col-md-4"><label class="form-label">Email</label>
        <input type="email" class="form-control" name="email" value="{{ old('email', $lead?->email) }}" {{ $readonly ? 'readonly' : '' }}>
    </div>
    <div class="col-md-3"><label class="form-label">City</label>
        <input type="text" class="form-control" name="city" value="{{ old('city', $lead?->city) }}" {{ $readonly ? 'readonly' : '' }}>
    </div>
    <div class="col-md-3"><label class="form-label">State</label>
        <select class="form-select" name="state_id" {{ $readonly ? 'disabled' : '' }}>
            <option value="">—</option>
            @foreach ($states as $state)
                <option value="{{ $state->id }}" @selected((string) old('state_id', $lead?->state_id) === (string) $state->id)>{{ $state->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3"><label class="form-label">Industry</label>
        <input type="text" class="form-control" name="industry" value="{{ old('industry', $lead?->industry) }}" {{ $readonly ? 'readonly' : '' }}>
    </div>
    <div class="col-md-3"><label class="form-label">Owner</label>
        <select class="form-select" name="assigned_user_id" {{ $readonly ? 'disabled' : '' }}>
            <option value="">—</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected((string) old('assigned_user_id', $lead?->assigned_user_id) === (string) $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3"><label class="form-label">Estimated Value</label>
        <input type="number" step="0.01" min="0" class="form-control" name="estimated_value" value="{{ old('estimated_value', $lead ? (float) $lead->estimated_value : 0) }}" {{ $readonly ? 'readonly' : '' }}>
    </div>
    <div class="col-md-3"><label class="form-label">Next Follow-up</label>
        <input type="date" class="form-control" name="next_follow_up_date" value="{{ old('next_follow_up_date', optional($lead?->next_follow_up_date)->format('Y-m-d')) }}" {{ $readonly ? 'readonly' : '' }}>
    </div>
    <div class="col-md-6"><label class="form-label">Requirement</label>
        <textarea class="form-control" name="requirement" rows="2" {{ $readonly ? 'readonly' : '' }}>{{ old('requirement', $lead?->requirement) }}</textarea>
    </div>
    <div class="col-12"><label class="form-label">Remarks</label>
        <textarea class="form-control" name="remarks" rows="2" {{ $readonly ? 'readonly' : '' }}>{{ old('remarks', $lead?->remarks) }}</textarea>
    </div>
</div>
@unless ($readonly)
<div class="mt-3">
    <button class="btn btn-primary" type="submit">Save Lead</button>
    <a href="{{ route('admin.leads.index') }}" class="btn btn-light">Cancel</a>
</div>
@endunless
</form>
</div></div>
