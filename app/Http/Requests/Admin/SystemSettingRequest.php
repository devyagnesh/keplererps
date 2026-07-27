<?php

namespace App\Http\Requests\Admin;

use App\Enums\CostingMethod;
use App\Enums\NumberFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates system settings update payload.
 */
class SystemSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'allow_negative_stock_default' => $this->boolean('allow_negative_stock_default'),
            'whatsapp_enabled' => $this->boolean('whatsapp_enabled'),
            'firebase_enabled' => $this->boolean('firebase_enabled'),
            'gsp_enabled' => $this->boolean('gsp_enabled'),
            'einvoice_enabled' => $this->boolean('einvoice_enabled'),
            'dashboard_show_pending_approvals' => $this->boolean('dashboard_show_pending_approvals'),
            'dashboard_show_overdue_crm' => $this->boolean('dashboard_show_overdue_crm'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'costing_method' => ['required', Rule::in(CostingMethod::values())],
            'timezone' => ['required', 'timezone'],
            'date_format' => ['required', 'string', 'max:20'],
            'number_format' => ['required', Rule::in(NumberFormat::values())],
            'allow_negative_stock_default' => ['required', 'boolean'],
            'stock_adjustment_approval_value' => ['nullable', 'numeric', 'min:0'],
            'slow_moving_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'whatsapp_enabled' => ['sometimes', 'boolean'],
            'whatsapp_token' => ['nullable', 'string', 'max:500'],
            'whatsapp_phone_number_id' => ['nullable', 'string', 'max:100'],
            'whatsapp_api_version' => ['nullable', 'string', 'max:20'],
            'whatsapp_verify_token' => ['nullable', 'string', 'max:100'],
            'whatsapp_template_dispatch' => ['nullable', 'string', 'max:100'],
            'whatsapp_template_salary_slip' => ['nullable', 'string', 'max:100'],
            'firebase_enabled' => ['sometimes', 'boolean'],
            'firebase_server_key' => ['nullable', 'string', 'max:500'],
            'gsp_enabled' => ['sometimes', 'boolean'],
            'gsp_base_url' => ['nullable', 'string', 'max:255'],
            'gsp_api_key' => ['nullable', 'string', 'max:500'],
            'gsp_gstin' => ['nullable', 'string', 'max:20'],
            'einvoice_enabled' => ['sometimes', 'boolean'],
            'einvoice_base_url' => ['nullable', 'string', 'max:255'],
            'einvoice_api_key' => ['nullable', 'string', 'max:500'],
            'dashboard_show_pending_approvals' => ['sometimes', 'boolean'],
            'dashboard_show_overdue_crm' => ['sometimes', 'boolean'],
        ];
    }
}
