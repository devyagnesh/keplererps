<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

/**
 * Validates role create/update.
 */
class RoleRequest extends FormRequest
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
            'simplified_ui' => $this->boolean('simplified_ui'),
            'slug' => $this->filled('slug')
                ? Str::slug((string) $this->input('slug'))
                : Str::slug((string) $this->input('name')),
            'permission_ids' => array_map('intval', (array) $this->input('permission_ids', [])),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('role')?->id;

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($id)->whereNull('deleted_at')],
            'slug' => ['required', 'string', 'max:100', Rule::unique('roles', 'slug')->ignore($id)->whereNull('deleted_at')],
            'description' => ['nullable', 'string', 'max:255'],
            'level' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'require_2fa' => ['required', 'boolean'],
            'simplified_ui' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'permission_ids' => ['array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ];
    }
}
