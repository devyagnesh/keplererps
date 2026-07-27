<?php

namespace App\Http\Requests\Admin;

use App\Enums\AddressType;
use App\Enums\GstType;
use App\Enums\PartyStatus;
use App\Enums\PartyType;
use App\Rules\Gstin;
use App\Rules\IndianMobile;
use App\Rules\IndianPinCode;
use App\Rules\Pan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates party (customer/supplier) create/update.
 */
class PartyRequest extends FormRequest
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
            'unlimited_credit' => $this->boolean('unlimited_credit'),
            'pan' => $this->filled('pan') ? strtoupper((string) $this->input('pan')) : null,
            'gstin' => $this->filled('gstin') ? strtoupper((string) $this->input('gstin')) : null,
            'bank_ifsc' => $this->filled('bank_ifsc') ? strtoupper((string) $this->input('bank_ifsc')) : null,
            'billing_country' => $this->input('billing_country', 'India'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $partyId = $this->route('party')?->id;

        return [
            'party_name' => ['required', 'string', 'min:2', 'max:150'],
            'party_type' => ['required', Rule::in(PartyType::values())],
            'gst_type' => ['required', Rule::in(GstType::values())],
            'gstin' => [
                Rule::requiredIf(fn () => $this->input('gst_type') === GstType::Registered->value),
                'nullable',
                'string',
                'size:15',
                new Gstin($this->input('pan')),
                Rule::unique('parties', 'gstin')->ignore($partyId)->whereNull('deleted_at'),
            ],
            'pan' => ['nullable', 'string', 'size:10', new Pan],
            'billing_line1' => ['required', 'string', 'max:150'],
            'billing_line2' => ['nullable', 'string', 'max:150'],
            'billing_city' => ['required', 'string', 'max:100'],
            'billing_state_id' => ['required', 'integer', 'exists:states,id'],
            'billing_pin_code' => ['required', 'string', new IndianPinCode],
            'billing_country' => ['required', 'string', 'max:100'],
            'credit_limit' => ['nullable', 'numeric', 'min:0', 'regex:/^\d{1,12}(\.\d{1,2})?$/'],
            'unlimited_credit' => ['required', 'boolean'],
            'credit_days' => ['nullable', 'integer', 'between:0,365'],
            'bank_account_name' => ['nullable', 'string', 'max:150'],
            'bank_account_number' => ['nullable', 'string', 'min:9', 'max:18', 'regex:/^[0-9]+$/'],
            'bank_account_number_confirmation' => ['nullable', 'same:bank_account_number'],
            'bank_ifsc' => ['nullable', 'string', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
            'bank_name' => ['nullable', 'string', 'max:150'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['required', Rule::in(PartyStatus::values())],
            'contacts' => ['required', 'array', 'min:1'],
            'contacts.*.name' => ['required', 'string', 'min:2', 'max:100'],
            'contacts.*.mobile' => ['required', new IndianMobile],
            'contacts.*.email' => ['nullable', 'email', 'max:100'],
            'contacts.*.designation' => ['nullable', 'string', 'max:100'],
            'contacts.*.whatsapp_opt_in' => ['sometimes', 'boolean'],
            'contacts.*.is_primary' => ['sometimes', 'boolean'],
            'addresses' => ['nullable', 'array'],
            'addresses.*.address_type' => ['required_with:addresses', Rule::in(AddressType::values())],
            'addresses.*.label' => ['nullable', 'string', 'max:100'],
            'addresses.*.line1' => ['required_with:addresses', 'string', 'max:150'],
            'addresses.*.line2' => ['nullable', 'string', 'max:150'],
            'addresses.*.city' => ['required_with:addresses', 'string', 'max:100'],
            'addresses.*.state_id' => ['required_with:addresses', 'integer', 'exists:states,id'],
            'addresses.*.pin_code' => ['required_with:addresses', 'string', new IndianPinCode],
            'addresses.*.country' => ['nullable', 'string', 'max:100'],
            'addresses.*.is_default' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'party_name.required' => 'Party name is required.',
            'gstin.unique' => 'This GSTIN is already registered to another party.',
            'contacts.required' => 'At least one contact person is required.',
            'contacts.*.mobile.required' => 'Contact mobile is required.',
            'bank_account_number_confirmation.same' => 'Account number confirmation does not match.',
            'bank_ifsc.regex' => 'IFSC must match the format ABCD0XXXXXX.',
        ];
    }
}
