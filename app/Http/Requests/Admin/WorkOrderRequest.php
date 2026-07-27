<?php

namespace App\Http\Requests\Admin;

use App\Enums\WorkOrderPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for work order create/update (M09).
 */
class WorkOrderRequest extends FormRequest
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
        $rules = [
            'document_date' => ['required', 'date'],
            'planned_quantity' => ['required', 'numeric', 'gt:0'],
            'planned_start_date' => ['required', 'date'],
            'planned_end_date' => ['required', 'date', 'after_or_equal:planned_start_date'],
            'source_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'target_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'work_centre_id' => ['nullable', 'integer', 'exists:work_centres,id'],
            'priority' => ['nullable', Rule::in(WorkOrderPriority::values())],
            'sales_order_id' => ['nullable', 'integer', 'exists:sales_orders,id'],
            'sales_order_item_id' => ['nullable', 'integer', 'exists:sales_order_items,id'],
            'bom_version_reason' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'confirm_non_critical' => ['nullable', 'boolean'],
        ];

        if ($this->isMethod('post')) {
            $rules['item_id'] = ['required', 'integer', 'exists:items,id'];
            $rules['bom_id'] = ['required', 'integer', 'exists:boms,id'];
        }

        return $rules;
    }
}
