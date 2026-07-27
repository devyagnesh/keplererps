<?php

namespace App\Http\Requests\Admin;

use App\Enums\WarehouseLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates warehouse create/update.
 */
class WarehouseRequest extends FormRequest
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
        $warehouseId = $this->route('warehouse')?->id;
        $branchId = $this->input('branch_id');

        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'parent_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'code' => [
                'required',
                'string',
                'min:2',
                'max:30',
                Rule::unique('warehouses', 'code')
                    ->ignore($warehouseId)
                    ->where(fn ($q) => $q->where('branch_id', $branchId)->whereNull('deleted_at')),
            ],
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'level' => ['required', Rule::in(WarehouseLevel::values())],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'branch_id.required' => 'Branch is required.',
            'code.required' => 'Warehouse code is required.',
            'code.unique' => 'This warehouse code already exists for the selected branch.',
            'level.in' => 'Level must be plant, store, rack or bin.',
        ];
    }
}
