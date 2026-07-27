<?php

namespace App\Http\Requests\Admin;

use App\Rules\Gstin;
use App\Rules\IndianPinCode;
use App\Rules\Pan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates company profile create/update (US-M01-01).
 */
class CompanyUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Prepare input before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_gst_registered' => $this->boolean('is_gst_registered'),
            'pan' => $this->filled('pan') ? strtoupper((string) $this->input('pan')) : null,
            'gstin' => $this->filled('gstin') ? strtoupper((string) $this->input('gstin')) : null,
            'email' => $this->filled('email') ? strtolower(trim((string) $this->input('email'))) : null,
            'phone' => $this->filled('phone') ? preg_replace('/[\s\-]/', '', (string) $this->input('phone')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'legal_name' => ['required', 'string', 'min:2', 'max:150', 'regex:/^[A-Za-z0-9\s\.\,\&\(\)\-\/]+$/'],
            'trade_name' => ['nullable', 'string', 'max:150'],
            'is_gst_registered' => ['required', 'boolean'],
            'gstin' => [
                Rule::requiredIf(fn () => $this->boolean('is_gst_registered')),
                'nullable',
                'string',
                'size:15',
                new Gstin($this->input('pan')),
            ],
            'pan' => ['required', 'string', 'size:10', new Pan],
            'cin' => ['nullable', 'string', 'size:21'],
            'registered_address' => ['required', 'string', 'min:10', 'max:250'],
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'pin_code' => ['required', 'string', new IndianPinCode],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{10,15}$/'],
            'email' => ['required', 'email', 'max:100'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:1024'],
            'fy_start_month' => ['required', 'integer', 'between:1,12'],
            'fy_start_day' => ['required', 'integer', 'between:1,31'],
            'base_currency' => ['required', 'string', 'size:3'],
            'amount_decimals' => ['required', 'integer', 'between:0,4'],
            'quantity_decimals' => ['required', 'integer', 'between:0,4'],
            'confirm_gstin_change' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'legal_name.required' => 'Legal name is required.',
            'legal_name.regex' => 'Legal name may only contain letters, digits, spaces and . , & ( ) - /',
            'pan.required' => 'PAN is required.',
            'registered_address.required' => 'Registered address is required.',
            'state_id.required' => 'State is required.',
            'email.required' => 'Email is required.',
            'logo.max' => 'Logo must be 1 MB or smaller.',
        ];
    }
}
