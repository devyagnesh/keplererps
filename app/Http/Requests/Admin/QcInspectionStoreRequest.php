<?php

namespace App\Http\Requests\Admin;

use App\Enums\InspectionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates manually raised QC inspections for non-incoming stages (US-M10-02).
 */
class QcInspectionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'document_date' => ['required', 'date'],
            'inspection_type' => [
                'required',
                Rule::in(array_diff(InspectionType::values(), [InspectionType::Incoming->value])),
            ],
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'lot_quantity' => ['required', 'numeric', 'gt:0'],
            'sample_size' => ['nullable', 'numeric', 'gt:0'],
            'sample_override_reason' => ['nullable', 'string', 'max:255'],
            'quarantine_warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'target_warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'work_order_id' => ['nullable', 'integer', 'exists:work_orders,id'],
            'sales_order_id' => ['nullable', 'integer', 'exists:sales_orders,id'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'inspection_type.in' => 'Incoming inspections are raised automatically from goods receipts.',
            'lot_quantity.gt' => 'Lot quantity must be greater than zero.',
        ];
    }
}
