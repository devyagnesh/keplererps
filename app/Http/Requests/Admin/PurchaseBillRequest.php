<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for purchase bill create/update (US-M07-04).
 */
class PurchaseBillRequest extends FormRequest
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
        $isUpdate = $this->route('purchase_bill') !== null;

        return [
            'document_date' => ['required', 'date'],
            'goods_receipt_id' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'exists:goods_receipts,id'],
            'supplier_bill_no' => ['required', 'string', 'max:60'],
            'supplier_bill_date' => ['required', 'date', 'before_or_equal:today'],
            'other_charges' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.goods_receipt_item_id' => ['required', 'integer', 'exists:goods_receipt_items,id'],
            'items.*.uom_id' => ['required', 'integer', 'exists:uoms,id'],
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
            'goods_receipt_id.required' => 'Select the goods receipt being billed.',
            'supplier_bill_no.required' => 'Supplier bill number is required.',
            'items.required' => 'Add at least one purchase bill line.',
        ];
    }
}
