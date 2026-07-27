<?php

namespace App\Http\Requests\Admin;

use App\Enums\BalanceSide;
use App\Enums\LedgerAccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for chart-of-accounts create/update.
 */
class LedgerAccountRequest extends FormRequest
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
        $id = $this->route('ledger_account')?->id;

        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('ledger_accounts', 'code')->ignore($id)->whereNull('deleted_at')],
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'account_type' => ['required', Rule::in(LedgerAccountType::values())],
            'account_group' => ['nullable', 'string', 'max:100'],
            'parent_id' => ['nullable', 'integer', 'exists:ledger_accounts,id'],
            'party_id' => ['nullable', 'integer', 'exists:parties,id'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'opening_balance_side' => ['nullable', Rule::in(BalanceSide::values())],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.unique' => 'This account code is already in use.',
            'account_type.in' => 'Choose a valid account type.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
