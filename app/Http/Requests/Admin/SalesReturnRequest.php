<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for sales return create/update.
 */
class SalesReturnRequest extends FormRequest
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
        $isUpdate = $this->route('sales_return') !== null;

        return [
            'document_date' => ['required', 'date'],
            'sales_invoice_id' => ['nullable', 'integer', 'exists:sales_invoices,id'],
            'customer_id' => [
                $isUpdate ? 'sometimes' : 'required_without:sales_invoice_id',
                'nullable',
                'integer',
                'exists:parties,id',
            ],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sales_invoice_item_id' => ['nullable', 'integer', 'exists:sales_invoice_items,id'],
            'items.*.item_id' => ['required_without:items.*.sales_invoice_item_id', 'nullable', 'integer', 'exists:items,id'],
            'items.*.uom_id' => ['required', 'integer', 'exists:uoms,id'],
            'items.*.batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.rate' => ['required', 'numeric', 'min:0'],
            'items.*.gst_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'State why the customer returned the goods.',
            'items.required' => 'Add at least one sales return line.',
        ];
    }
}
