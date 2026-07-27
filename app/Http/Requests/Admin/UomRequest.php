<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates UOM create/update.
 */
class UomRequest extends FormRequest
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
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('uom')?->id;

        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('uoms', 'code')->ignore($id)->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:100'],
            'uom_type' => ['required', Rule::in(['count', 'weight', 'length', 'volume', 'area', 'other'])],
            'decimal_places' => ['required', 'integer', 'between:0,4'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
