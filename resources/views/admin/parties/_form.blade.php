@php
    $contacts = old('contacts', $party?->contacts?->toArray() ?? [['name' => '', 'mobile' => '', 'email' => '', 'designation' => '', 'whatsapp_opt_in' => false]]);
@endphp
<div class="card custom-card">
    <div class="card-body">
        <form id="partyForm" action="{{ $action }}" method="POST" novalidate>
            @csrf
            <input type="hidden" name="_method" value="{{ $method }}">
            <div class="row gy-3">
                <div class="col-xl-6">
                    <label class="form-label" for="party_name">Party Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="party_name" name="party_name" value="{{ old('party_name', $party?->party_name) }}">
                </div>
                <div class="col-xl-3">
                    <label class="form-label" for="party_type">Party Type <span class="text-danger">*</span></label>
                    <select class="form-control" id="party_type" name="party_type">
                        @foreach($partyTypes as $type)
                            <option value="{{ $type->value }}" {{ old('party_type', $party?->party_type?->value) === $type->value ? 'selected' : '' }}>{{ ucfirst($type->value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xl-3">
                    <label class="form-label" for="gst_type">GST Type <span class="text-danger">*</span></label>
                    <select class="form-control" id="gst_type" name="gst_type">
                        @foreach($gstTypes as $type)
                            <option value="{{ $type->value }}" {{ old('gst_type', $party?->gst_type?->value ?? 'unregistered') === $type->value ? 'selected' : '' }}>{{ str_replace('_', ' ', ucfirst($type->value)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xl-4">
                    <label class="form-label" for="gstin">GSTIN</label>
                    <input type="text" class="form-control text-uppercase" id="gstin" name="gstin" maxlength="15" value="{{ old('gstin', $party?->gstin) }}">
                    <small class="text-muted" id="gstinHint"></small>
                </div>
                <div class="col-xl-4">
                    <label class="form-label" for="pan">PAN</label>
                    <input type="text" class="form-control text-uppercase" id="pan" name="pan" maxlength="10" value="{{ old('pan', $party?->pan) }}">
                </div>
                <div class="col-xl-4">
                    <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                    <select class="form-control" id="status" name="status">
                        @foreach($statuses as $status)
                            <option value="{{ $status->value }}" {{ old('status', $party?->status?->value ?? 'active') === $status->value ? 'selected' : '' }}>{{ ucfirst($status->value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xl-6">
                    <label class="form-label" for="billing_line1">Billing Address Line 1 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="billing_line1" name="billing_line1" value="{{ old('billing_line1', $party?->billing_line1) }}">
                </div>
                <div class="col-xl-6">
                    <label class="form-label" for="billing_line2">Billing Address Line 2</label>
                    <input type="text" class="form-control" id="billing_line2" name="billing_line2" value="{{ old('billing_line2', $party?->billing_line2) }}">
                </div>
                <div class="col-xl-3">
                    <label class="form-label" for="billing_city">City <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="billing_city" name="billing_city" value="{{ old('billing_city', $party?->billing_city) }}">
                </div>
                <div class="col-xl-3">
                    <label class="form-label" for="billing_state_id">State <span class="text-danger">*</span></label>
                    <select class="form-control select2" id="billing_state_id" name="billing_state_id">
                        <option value="">Select state</option>
                        @foreach($states as $state)
                            <option value="{{ $state->id }}" data-code="{{ $state->code }}" {{ (string) old('billing_state_id', $party?->billing_state_id) === (string) $state->id ? 'selected' : '' }}>
                                {{ $state->code }} — {{ $state->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xl-3">
                    <label class="form-label" for="billing_pin_code">PIN Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="billing_pin_code" name="billing_pin_code" maxlength="6" value="{{ old('billing_pin_code', $party?->billing_pin_code) }}">
                </div>
                <div class="col-xl-3">
                    <label class="form-label" for="billing_country">Country</label>
                    <input type="text" class="form-control" id="billing_country" name="billing_country" value="{{ old('billing_country', $party?->billing_country ?? 'India') }}">
                </div>
                <div class="col-xl-3">
                    <label class="form-label" for="credit_limit">Credit Limit</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="credit_limit" name="credit_limit" value="{{ old('credit_limit', $party?->credit_limit ?? 0) }}">
                </div>
                <div class="col-xl-3">
                    <div class="form-check form-switch mt-4">
                        <input type="hidden" name="unlimited_credit" value="0">
                        <input class="form-check-input" type="checkbox" id="unlimited_credit" name="unlimited_credit" value="1" {{ old('unlimited_credit', $party?->unlimited_credit) ? 'checked' : '' }}>
                        <label class="form-check-label" for="unlimited_credit">Unlimited Credit</label>
                    </div>
                </div>
                <div class="col-xl-3">
                    <label class="form-label" for="credit_days">Credit Days</label>
                    <input type="number" min="0" max="365" class="form-control" id="credit_days" name="credit_days" value="{{ old('credit_days', $party?->credit_days) }}">
                </div>
            </div>

            <hr class="my-4">
            <h6 class="fw-semibold mb-3">Contact Persons <span class="text-danger">*</span></h6>
            <div id="contactsWrapper">
                @foreach($contacts as $index => $contact)
                    <div class="row gy-2 contact-row mb-2" data-index="{{ $index }}">
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="contacts[{{ $index }}][name]" placeholder="Name" value="{{ $contact['name'] ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="contacts[{{ $index }}][mobile]" placeholder="Mobile" value="{{ $contact['mobile'] ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <input type="email" class="form-control" name="contacts[{{ $index }}][email]" placeholder="Email" value="{{ $contact['email'] ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control" name="contacts[{{ $index }}][designation]" placeholder="Designation" value="{{ $contact['designation'] ?? '' }}">
                        </div>
                        <div class="col-md-1 d-flex align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="contacts[{{ $index }}][whatsapp_opt_in]" value="1" {{ !empty($contact['whatsapp_opt_in']) ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <button type="button" class="btn btn-sm btn-light mb-3" id="addContactBtn">Add Contact</button>

            <hr class="my-3">
            <h6 class="fw-semibold mb-3">Bank Details (optional)</h6>
            <div class="row gy-3">
                <div class="col-xl-4">
                    <label class="form-label" for="bank_name">Bank Name</label>
                    <input type="text" class="form-control" id="bank_name" name="bank_name" value="{{ old('bank_name', $party?->bank_name) }}">
                </div>
                <div class="col-xl-4">
                    <label class="form-label" for="bank_account_name">Account Name</label>
                    <input type="text" class="form-control" id="bank_account_name" name="bank_account_name" value="{{ old('bank_account_name', $party?->bank_account_name) }}">
                </div>
                <div class="col-xl-4">
                    <label class="form-label" for="bank_ifsc">IFSC</label>
                    <input type="text" class="form-control text-uppercase" id="bank_ifsc" name="bank_ifsc" value="{{ old('bank_ifsc', $party?->bank_ifsc) }}">
                </div>
                <div class="col-xl-4">
                    <label class="form-label" for="bank_account_number">Account Number</label>
                    <input type="text" class="form-control" id="bank_account_number" name="bank_account_number" value="{{ old('bank_account_number', $party?->bank_account_number) }}">
                </div>
                <div class="col-xl-4">
                    <label class="form-label" for="bank_account_number_confirmation">Confirm Account Number</label>
                    <input type="text" class="form-control" id="bank_account_number_confirmation" name="bank_account_number_confirmation" value="{{ old('bank_account_number_confirmation', $party?->bank_account_number) }}">
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Save Party</button>
                <a href="{{ route('admin.parties.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
