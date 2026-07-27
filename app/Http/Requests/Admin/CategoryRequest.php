<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates category create/update.
 */
class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'code' => $this->filled('code') ? strtoupper(trim((string) $this->input('code'))) : null,
            'parent_id' => $this->filled('parent_id') ? $this->input('parent_id') : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('category')?->id;

        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('categories', 'code')->ignore($id)->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:150'],
            'category_type' => ['required', Rule::in(['item', 'party', 'other'])],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
