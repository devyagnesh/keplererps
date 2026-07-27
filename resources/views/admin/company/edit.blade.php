@extends('admin.layouts.app')

@section('title', 'Company Setup')

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Company Setup</h1>
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Company</li>
    </ol>
</div>
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Legal & GST Details</div>
            </div>
            <div class="card-body">
                <form id="companyForm" action="{{ route('admin.company.update') }}" method="POST" enctype="multipart/form-data" novalidate>
                    @csrf
                    <input type="hidden" name="confirm_gstin_change" id="confirm_gstin_change" value="0">
                    <div class="row gy-3">
                        <div class="col-xl-6">
                            <label class="form-label" for="legal_name">Legal Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="legal_name" name="legal_name" value="{{ old('legal_name', $company?->legal_name) }}">
                        </div>
                        <div class="col-xl-6">
                            <label class="form-label" for="trade_name">Trade Name</label>
                            <input type="text" class="form-control" id="trade_name" name="trade_name" value="{{ old('trade_name', $company?->trade_name) }}">
                        </div>
                        <div class="col-xl-4">
                            <label class="form-label" for="pan">PAN <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" id="pan" name="pan" maxlength="10" value="{{ old('pan', $company?->pan) }}">
                        </div>
                        <div class="col-xl-4">
                            <div class="form-check form-switch mt-4">
                                <input type="hidden" name="is_gst_registered" value="0">
                                <input class="form-check-input" type="checkbox" id="is_gst_registered" name="is_gst_registered" value="1" {{ old('is_gst_registered', $company?->is_gst_registered) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_gst_registered">GST Registered</label>
                            </div>
                        </div>
                        <div class="col-xl-4">
                            <label class="form-label" for="gstin">GSTIN</label>
                            <input type="text" class="form-control text-uppercase" id="gstin" name="gstin" maxlength="15" value="{{ old('gstin', $company?->gstin) }}">
                        </div>
                        <div class="col-xl-4">
                            <label class="form-label" for="cin">CIN</label>
                            <input type="text" class="form-control text-uppercase" id="cin" name="cin" maxlength="21" value="{{ old('cin', $company?->cin) }}">
                        </div>
                        <div class="col-xl-8">
                            <label class="form-label" for="registered_address">Registered Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="registered_address" name="registered_address" rows="2">{{ old('registered_address', $company?->registered_address) }}</textarea>
                        </div>
                        <div class="col-xl-4">
                            <label class="form-label" for="state_id">State <span class="text-danger">*</span></label>
                            <select class="form-control select2" id="state_id" name="state_id">
                                <option value="">Select state</option>
                                @foreach($states as $state)
                                    <option value="{{ $state->id }}" data-code="{{ $state->code }}" {{ (string) old('state_id', $company?->state_id) === (string) $state->id ? 'selected' : '' }}>
                                        {{ $state->code }} — {{ $state->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-xl-4">
                            <label class="form-label" for="pin_code">PIN Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="pin_code" name="pin_code" maxlength="6" value="{{ old('pin_code', $company?->pin_code) }}">
                        </div>
                        <div class="col-xl-4">
                            <label class="form-label" for="phone">Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $company?->phone) }}">
                        </div>
                        <div class="col-xl-4">
                            <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $company?->email) }}">
                        </div>
                        <div class="col-xl-4">
                            <label class="form-label" for="logo">Logo (PNG/JPG, max 1 MB)</label>
                            <input type="file" class="form-control" id="logo" name="logo" accept=".png,.jpg,.jpeg">
                            @if($company?->logo_path)
                                <div class="mt-2">
                                    <img src="{{ asset('storage/'.$company->logo_path) }}" alt="Logo" height="48">
                                </div>
                            @endif
                        </div>
                        <div class="col-xl-3">
                            <label class="form-label" for="fy_start_month">FY Start Month</label>
                            <select class="form-control" id="fy_start_month" name="fy_start_month" {{ $company?->has_transactions ? 'disabled' : '' }}>
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ (int) old('fy_start_month', $company?->fy_start_month ?? 4) === $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                                @endfor
                            </select>
                            @if($company?->has_transactions)
                                <input type="hidden" name="fy_start_month" value="{{ $company->fy_start_month }}">
                            @endif
                        </div>
                        <div class="col-xl-3">
                            <label class="form-label" for="fy_start_day">FY Start Day</label>
                            <input type="number" class="form-control" id="fy_start_day" name="fy_start_day" min="1" max="31" value="{{ old('fy_start_day', $company?->fy_start_day ?? 1) }}" {{ $company?->has_transactions ? 'readonly' : '' }}>
                        </div>
                        <div class="col-xl-2">
                            <label class="form-label" for="base_currency">Currency</label>
                            <input type="text" class="form-control text-uppercase" id="base_currency" name="base_currency" maxlength="3" value="{{ old('base_currency', $company?->base_currency ?? 'INR') }}" {{ $company?->has_transactions ? 'readonly' : '' }}>
                        </div>
                        <div class="col-xl-2">
                            <label class="form-label" for="amount_decimals">Amount Decimals</label>
                            <input type="number" class="form-control" id="amount_decimals" name="amount_decimals" min="0" max="4" value="{{ old('amount_decimals', $company?->amount_decimals ?? 2) }}">
                        </div>
                        <div class="col-xl-2">
                            <label class="form-label" for="quantity_decimals">Qty Decimals</label>
                            <input type="number" class="form-control" id="quantity_decimals" name="quantity_decimals" min="0" max="4" value="{{ old('quantity_decimals', $company?->quantity_decimals ?? 3) }}">
                        </div>
                        <div class="col-xl-12">
                            <button type="submit" class="btn btn-primary">Save Company</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.companyHasTransactions = @json((bool) ($company?->has_transactions));
    window.companyCurrentGstin = @json($company?->gstin);
</script>
<script src="{{ asset('assets/admin/js/admin/company/company.js') }}"></script>
@endpush
