<?php

namespace App\Http\Requests\Admin;

use App\Rules\IndianPinCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates branch create/update.
 */
class BranchRequest extends FormRequest
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
            'is_head_office' => $this->boolean('is_head_office'),
            'is_active' => $this->boolean('is_active'),
            'code' => $this->filled('code') ? strtoupper(trim((string) $this->input('code'))) : null,
            'email' => $this->filled('email') ? strtolower(trim((string) $this->input('email'))) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $branchId = $this->route('branch')?->id;

        return [
            'code' => [
                'required',
                'string',
                'min:2',
                'max:30',
                Rule::unique('branches', 'code')->ignore($branchId)->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'address' => ['nullable', 'string', 'max:250'],
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'pin_code' => ['nullable', 'string', new IndianPinCode],
            'phone' => ['nullable', 'string', 'regex:/^\+?[0-9]{10,15}$/'],
            'email' => ['nullable', 'email', 'max:100'],
            'is_head_office' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Branch code is required.',
            'code.unique' => 'This branch code is already taken.',
            'name.required' => 'Branch name is required.',
        ];
    }
}
