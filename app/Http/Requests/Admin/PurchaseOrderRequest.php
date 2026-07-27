<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for purchase order create/update (M07).
 */
class PurchaseOrderRequest extends FormRequest
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
            'supplier_id' => ['required', 'integer', 'exists:parties,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'expected_delivery_date' => ['required', 'date', 'after_or_equal:document_date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'items.*.uom_id' => ['required', 'integer', 'exists:uoms,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.rate' => ['required', 'numeric', 'min:0'],
            'items.*.gst_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tolerance_percent' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'items.*.requires_inspection' => ['nullable', 'boolean'],
        ];
    }
}
