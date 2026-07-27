<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for sales order create/update (M06).
 */
class SalesOrderRequest extends FormRequest
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
            'customer_id' => ['required', 'integer', 'exists:parties,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'place_of_supply_state_id' => ['required', 'integer', 'exists:states,id'],
            'quotation_id' => ['nullable', 'integer', 'exists:sales_quotations,id'],
            'customer_po_no' => ['nullable', 'string', 'max:50'],
            'customer_po_date' => ['nullable', 'date'],
            'expected_delivery_date' => ['required', 'date', 'after_or_equal:document_date'],
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
