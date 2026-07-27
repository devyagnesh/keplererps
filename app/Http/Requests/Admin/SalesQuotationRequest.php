<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for sales quotation create/update (M06).
 */
class SalesQuotationRequest extends FormRequest
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
            'valid_until' => ['nullable', 'date', 'after_or_equal:document_date'],
            'customer_id' => ['required', 'integer', 'exists:parties,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'place_of_supply_state_id' => ['required', 'integer', 'exists:states,id'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'items.*.uom_id' => ['required', 'integer', 'exists:uoms,id'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.rate' => ['required', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.gst_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
