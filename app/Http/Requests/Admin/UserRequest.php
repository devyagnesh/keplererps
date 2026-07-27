<?php

namespace App\Http\Requests\Admin;

use App\Enums\DataScopeType;
use App\Rules\IndianMobile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Validates user create/update.
 */
class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'require_2fa' => $this->boolean('require_2fa'),
            'username' => $this->filled('username') ? strtolower(trim((string) $this->input('username'))) : null,
            'email' => $this->filled('email') ? strtolower(trim((string) $this->input('email'))) : null,
            'mobile' => $this->filled('mobile') ? preg_replace('/[\s\-]/', '', (string) $this->input('mobile')) : null,
            'role_ids' => array_map('intval', (array) $this->input('role_ids', [])),
            'scope_branch_ids' => array_values(array_filter(array_map('intval', (array) $this->input('scope_branch_ids', [])))),
            'scope_warehouse_ids' => array_values(array_filter(array_map('intval', (array) $this->input('scope_warehouse_ids', [])))),
            'scope_team_user_ids' => array_values(array_filter(array_map('intval', (array) $this->input('scope_team_user_ids', [])))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;
        $isUpdate = $userId !== null;

        return [
            'name' => ['required', 'string', 'min:2', 'max:100', 'regex:/^[A-Za-z\s\.\']+$/'],
            'username' => [
                Rule::requiredIf(! $isUpdate),
                'nullable',
                'string',
                'min:4',
                'max:30',
                'regex:/^[a-z0-9._]+$/',
                Rule::unique('users', 'username')->ignore($userId)->whereNull('deleted_at'),
            ],
            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('users', 'email')->ignore($userId)->whereNull('deleted_at'),
            ],
            'mobile' => [
                'required',
                new IndianMobile,
                Rule::unique('users', 'mobile')->ignore($userId)->whereNull('deleted_at'),
            ],
            'password' => [
                Rule::requiredIf(! $isUpdate),
                'nullable',
                'confirmed',
                Password::min(8)->letters()->numbers(),
            ],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            'scope_type' => ['required', Rule::in(DataScopeType::values())],
            'scope_branch_ids' => ['nullable', 'array'],
            'scope_branch_ids.*' => ['integer', 'exists:branches,id'],
            'scope_warehouse_ids' => ['nullable', 'array'],
            'scope_warehouse_ids.*' => ['integer', 'exists:warehouses,id'],
            'scope_team_user_ids' => ['nullable', 'array'],
            'scope_team_user_ids.*' => ['integer', 'exists:users,id'],
            'require_2fa' => ['required', 'boolean'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
