<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for the packing unit master (M17).
 */
class PackingUnitRequest extends FormRequest
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
        $id = $this->route('packing_unit')?->id;

        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('packing_units', 'code')->ignore($id)->whereNull('deleted_at')],
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'item_id' => ['nullable', 'integer', 'exists:items,id'],
            'parent_id' => ['nullable', 'integer', 'exists:packing_units,id'],
            'uom_id' => ['required', 'integer', 'exists:uoms,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'is_active' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Packing unit code is required.',
            'code.unique' => 'This packing unit code is already taken.',
            'quantity.gt' => 'Quantity per unit must be greater than zero.',
        ];
    }
}
