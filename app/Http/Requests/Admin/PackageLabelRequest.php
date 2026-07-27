<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for printing package labels against a challan line (M17).
 */
class PackageLabelRequest extends FormRequest
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
            'delivery_challan_id' => ['required', 'integer', 'exists:delivery_challans,id'],
            'delivery_challan_item_id' => ['required', 'integer', 'exists:delivery_challan_items,id'],
            'packing_unit_id' => ['required', 'integer', 'exists:packing_units,id'],
            'package_count' => ['required', 'integer', 'min:1', 'max:500'],
            'quantity_per_package' => ['nullable', 'numeric', 'gt:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'delivery_challan_item_id.required' => 'Select the challan line being packed.',
            'packing_unit_id.required' => 'Select a packing unit.',
            'package_count.max' => 'Print at most 500 labels at a time.',
        ];
    }
}
