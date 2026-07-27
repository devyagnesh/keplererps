<?php

namespace App\Http\Requests\Admin;

use App\Enums\VoucherType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for journal voucher create/update.
 */
class JournalVoucherRequest extends FormRequest
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
            'document_date' => ['required', 'date'],
            // The voucher type is fixed at creation; updates keep the existing series.
            'voucher_type' => [$this->isMethod('POST') ? 'required' : 'nullable', Rule::in(VoucherType::values())],
            'reference_no' => ['nullable', 'string', 'max:60'],
            'narration' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.ledger_account_id' => ['required', 'integer', 'exists:ledger_accounts,id'],
            'lines.*.party_id' => ['nullable', 'integer', 'exists:parties,id'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.narration' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lines.required' => 'A voucher needs at least one debit and one credit line.',
            'lines.min' => 'A voucher needs at least one debit and one credit line.',
        ];
    }
}
