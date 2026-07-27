<?php

namespace App\Http\Requests\Admin;

use App\Enums\ChargeAllocationBasis;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for goods receipt create/update (M07).
 */
class GoodsReceiptRequest extends FormRequest
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
        $isUpdate = $this->route('goods_receipt') !== null;

        return [
            'document_date' => ['required', 'date'],
            'purchase_order_id' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'exists:purchase_orders,id'],
            'supplier_invoice_no' => ['required', 'string', 'max:50'],
            'supplier_invoice_date' => ['required', 'date', 'before_or_equal:today'],
            'vehicle_number' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z]{2}[0-9]{1,2}[A-Z]{0,3}[0-9]{4}$/'],
            'freight_charges' => ['nullable', 'numeric', 'min:0'],
            'other_charges' => ['nullable', 'numeric', 'min:0'],
            'charge_allocation_basis' => ['nullable', Rule::enum(ChargeAllocationBasis::class)],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['required', 'integer', 'exists:purchase_order_items,id'],
            'items.*.received_qty' => ['required', 'numeric', 'gt:0'],
            'items.*.accepted_qty' => ['required', 'numeric', 'min:0'],
            'items.*.rejected_qty' => ['nullable', 'numeric', 'min:0'],
            'items.*.rejection_reason' => ['nullable', 'string', 'max:255'],
            'items.*.rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.batch_no' => ['nullable', 'string', 'max:50'],
            'items.*.mfg_date' => ['nullable', 'date'],
            'items.*.expiry_date' => ['nullable', 'date', 'after:items.*.mfg_date'],
            'items.*.serial_no' => ['nullable', 'string', 'max:80'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'vehicle_number.regex' => 'Enter a valid Indian vehicle number (e.g. GJ01AB1234).',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('vehicle_number')) {
            $this->merge([
                'vehicle_number' => strtoupper(preg_replace('/\s+/', '', (string) $this->input('vehicle_number')) ?? ''),
            ]);
        }
    }
}
