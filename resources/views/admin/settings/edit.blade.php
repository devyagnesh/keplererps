@extends('admin.layouts.app')
@section('title', 'System Settings')
@section('content')
<div class="my-4"><h1 class="page-title fw-semibold fs-18 mb-0">System Settings</h1></div>
<div class="card custom-card"><div class="card-body">
<form id="settingsForm" action="{{ route('admin.settings.update') }}" method="POST" novalidate>
@csrf
<input type="hidden" name="_method" value="PUT">
@php
    $flat = $grouped->flatten(1)->keyBy('setting_key');
@endphp
<div class="row gy-3">
    <div class="col-12"><h6 class="fw-semibold">Industry / Inventory</h6></div>
    <div class="col-md-4">
        <label class="form-label">Costing method</label>
        @php $costing = $flat->get('costing_method'); @endphp
        <select name="costing_method" class="form-select" {{ $costing?->is_locked ? 'disabled' : '' }}>
            @foreach ($costingMethods as $method)
                <option value="{{ $method->value }}" @selected(old('costing_method', $costing?->setting_value) === $method->value)>{{ $method->label() }}</option>
            @endforeach
        </select>
        @if ($costing?->is_locked)
            <input type="hidden" name="costing_method" value="{{ $costing->setting_value }}">
            <small class="text-muted">Locked after first financial year close.</small>
        @endif
    </div>
    <div class="col-md-4">
        <label class="form-label">Slow-moving days</label>
        <input type="number" min="1" class="form-control" name="slow_moving_days" value="{{ old('slow_moving_days', $flat->get('slow_moving_days')?->setting_value ?? 90) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Adjustment approval value</label>
        <input type="number" step="0.01" min="0" class="form-control" name="stock_adjustment_approval_value" value="{{ old('stock_adjustment_approval_value', $flat->get('stock_adjustment_approval_value')?->setting_value ?? 0) }}">
    </div>
    <div class="col-md-4">
        <div class="form-check form-switch mt-4">
            <input type="hidden" name="allow_negative_stock_default" value="0">
            <input class="form-check-input" type="checkbox" name="allow_negative_stock_default" value="1" {{ old('allow_negative_stock_default', $flat->get('allow_negative_stock_default')?->typedValue()) ? 'checked' : '' }}>
            <label class="form-check-label">Default allow negative stock on new warehouses</label>
        </div>
    </div>

    <div class="col-12 mt-3"><h6 class="fw-semibold">Localisation</h6></div>
    <div class="col-md-4">
        <label class="form-label">Timezone</label>
        <select name="timezone" class="form-select select2">
            @foreach ($timezones as $tz)
                <option value="{{ $tz }}" @selected(old('timezone', $flat->get('timezone')?->setting_value ?? config('app.timezone')) === $tz)>{{ $tz }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Date format</label>
        <select name="date_format" class="form-select">
            @foreach (['d-m-Y', 'Y-m-d', 'd/m/Y', 'm/d/Y'] as $fmt)
                <option value="{{ $fmt }}" @selected(old('date_format', $flat->get('date_format')?->setting_value ?? 'd-m-Y') === $fmt)>{{ $fmt }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Number format</label>
        <select name="number_format" class="form-select">
            @foreach ($numberFormats as $fmt)
                <option value="{{ $fmt->value }}" @selected(old('number_format', $flat->get('number_format')?->setting_value) === $fmt->value)>{{ $fmt->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-12 mt-3"><h6 class="fw-semibold">Integrations</h6></div>
    <div class="col-12">
        <small class="text-muted">Demo credentials are seeded for local testing.</small>
    </div>
    <div class="col-md-4">
        <div class="form-check form-switch mt-4">
            <input type="hidden" name="whatsapp_enabled" value="0">
            <input class="form-check-input" type="checkbox" name="whatsapp_enabled" value="1" {{ old('whatsapp_enabled', $flat->get('whatsapp_enabled')?->typedValue()) ? 'checked' : '' }}>
            <label class="form-check-label">Enable WhatsApp</label>
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label">WhatsApp phone number ID</label>
        <input type="text" class="form-control" name="whatsapp_phone_number_id" value="{{ old('whatsapp_phone_number_id', $flat->get('whatsapp_phone_number_id')?->setting_value) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">WhatsApp API version</label>
        <input type="text" class="form-control" name="whatsapp_api_version" value="{{ old('whatsapp_api_version', $flat->get('whatsapp_api_version')?->setting_value ?? 'v19.0') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">WhatsApp webhook verify token</label>
        <input type="text" class="form-control" name="whatsapp_verify_token" value="{{ old('whatsapp_verify_token', $flat->get('whatsapp_verify_token')?->setting_value ?? 'kepler-whatsapp-verify') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Dispatch template name</label>
        <input type="text" class="form-control" name="whatsapp_template_dispatch" value="{{ old('whatsapp_template_dispatch', $flat->get('whatsapp_template_dispatch')?->setting_value ?? 'goods_dispatched') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Salary slip template name</label>
        <input type="text" class="form-control" name="whatsapp_template_salary_slip" value="{{ old('whatsapp_template_salary_slip', $flat->get('whatsapp_template_salary_slip')?->setting_value ?? 'salary_slip') }}">
    </div>
    <div class="col-md-8">
        <label class="form-label">WhatsApp access token</label>
        <input type="password" class="form-control" name="whatsapp_token" value="{{ old('whatsapp_token', $flat->get('whatsapp_token')?->setting_value) }}" autocomplete="new-password">
    </div>
    <div class="col-md-4">
        <div class="form-check form-switch mt-4">
            <input type="hidden" name="firebase_enabled" value="0">
            <input class="form-check-input" type="checkbox" name="firebase_enabled" value="1" {{ old('firebase_enabled', $flat->get('firebase_enabled')?->typedValue()) ? 'checked' : '' }}>
            <label class="form-check-label">Enable Firebase push</label>
        </div>
    </div>
    <div class="col-md-8">
        <label class="form-label">Firebase server key</label>
        <input type="password" class="form-control" name="firebase_server_key" value="{{ old('firebase_server_key', $flat->get('firebase_server_key')?->setting_value) }}" autocomplete="new-password">
    </div>

    <div class="col-md-4">
        <div class="form-check form-switch mt-4">
            <input type="hidden" name="gsp_enabled" value="0">
            <input class="form-check-input" type="checkbox" name="gsp_enabled" value="1" {{ old('gsp_enabled', $flat->get('gsp_enabled')?->typedValue()) ? 'checked' : '' }}>
            <label class="form-check-label">Enable GST GSP</label>
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label">GSP base URL</label>
        <input type="text" class="form-control" name="gsp_base_url" value="{{ old('gsp_base_url', $flat->get('gsp_base_url')?->setting_value) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Company GSTIN (GSP)</label>
        <input type="text" class="form-control" name="gsp_gstin" value="{{ old('gsp_gstin', $flat->get('gsp_gstin')?->setting_value) }}">
    </div>
    <div class="col-md-8">
        <label class="form-label">GSP API key</label>
        <input type="password" class="form-control" name="gsp_api_key" value="{{ old('gsp_api_key', $flat->get('gsp_api_key')?->setting_value) }}" autocomplete="new-password">
    </div>

    <div class="col-md-4">
        <div class="form-check form-switch mt-4">
            <input type="hidden" name="einvoice_enabled" value="0">
            <input class="form-check-input" type="checkbox" name="einvoice_enabled" value="1" {{ old('einvoice_enabled', $flat->get('einvoice_enabled')?->typedValue()) ? 'checked' : '' }}>
            <label class="form-check-label">Enable e-invoice push</label>
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label">E-invoice base URL</label>
        <input type="text" class="form-control" name="einvoice_base_url" value="{{ old('einvoice_base_url', $flat->get('einvoice_base_url')?->setting_value) }}">
    </div>
    <div class="col-md-8">
        <label class="form-label">E-invoice API key</label>
        <input type="password" class="form-control" name="einvoice_api_key" value="{{ old('einvoice_api_key', $flat->get('einvoice_api_key')?->setting_value) }}" autocomplete="new-password">
    </div>

    <div class="col-12 mt-3"><h6 class="fw-semibold">Dashboard widgets</h6></div>
    <div class="col-md-4">
        <div class="form-check form-switch">
            <input type="hidden" name="dashboard_show_pending_approvals" value="0">
            <input class="form-check-input" type="checkbox" name="dashboard_show_pending_approvals" value="1" {{ old('dashboard_show_pending_approvals', $flat->get('dashboard_show_pending_approvals')?->typedValue() ?? true) ? 'checked' : '' }}>
            <label class="form-check-label">Pending approvals</label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-check form-switch">
            <input type="hidden" name="dashboard_show_overdue_crm" value="0">
            <input class="form-check-input" type="checkbox" name="dashboard_show_overdue_crm" value="1" {{ old('dashboard_show_overdue_crm', $flat->get('dashboard_show_overdue_crm')?->typedValue() ?? true) ? 'checked' : '' }}>
            <label class="form-check-label">Overdue CRM follow-ups</label>
        </div>
    </div>

    <div class="col-12"><button type="submit" class="btn btn-primary">Save Settings</button></div>
</div>
</form>
</div></div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/settings/form.js') }}"></script>
@endpush
