<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for production plan create/update.
 */
class ProductionPlanRequest extends FormRequest
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
            'plan_from_date' => ['required', 'date'],
            'plan_to_date' => ['required', 'date', 'after_or_equal:plan_from_date'],
            'source_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'target_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'items.*.bom_id' => ['nullable', 'integer', 'exists:boms,id'],
            'items.*.sales_order_id' => ['nullable', 'integer', 'exists:sales_orders,id'],
            'items.*.sales_order_item_id' => ['nullable', 'integer', 'exists:sales_order_items,id'],
            'items.*.planned_quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.required_date' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Add at least one production plan line.',
            'plan_to_date.after_or_equal' => 'Plan end date cannot be before the start date.',
        ];
    }
}
