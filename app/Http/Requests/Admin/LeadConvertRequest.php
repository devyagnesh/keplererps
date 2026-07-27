<?php

namespace App\Http\Requests\Admin;

use App\Enums\GstType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for converting a lead into a customer party (M05).
 */
class LeadConvertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'gst_type' => ['required', Rule::in(GstType::values())],
            'gstin' => ['nullable', 'string', 'size:15'],
            'pan' => ['nullable', 'string', 'size:10'],
            'billing_line1' => ['required', 'string', 'max:255'],
            'billing_line2' => ['nullable', 'string', 'max:255'],
            'billing_city' => ['required', 'string', 'max:80'],
            'billing_state_id' => ['required', 'integer', 'exists:states,id'],
            'billing_pin_code' => ['required', 'string', 'size:6'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'credit_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'create_opportunity' => ['nullable', 'boolean'],
            'opportunity_title' => ['nullable', 'string', 'max:150'],
            'expected_value' => ['nullable', 'numeric', 'min:0'],
            'expected_close_date' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'billing_line1.required' => 'Billing address is required to create the customer.',
            'billing_state_id.required' => 'Billing state is required for GST place of supply.',
        ];
    }
}
