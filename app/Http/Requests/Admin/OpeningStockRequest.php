<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validates opening stock create/update.
 */
class OpeningStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'items' => array_values(array_filter(
                $this->input('items', []),
                fn ($row) => is_array($row) && ! empty($row['item_id']) && ! empty($row['quantity'])
            )),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'document_date' => ['required', 'date'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'items.*.batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'items.*.batch_no' => ['nullable', 'string', 'max:50'],
            'items.*.serial_no' => ['nullable', 'string', 'max:80'],
            'items.*.mfg_date' => ['nullable', 'date'],
            'items.*.expiry_date' => ['nullable', 'date', 'after_or_equal:items.*.mfg_date'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.rate' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $warehouse = \App\Models\Warehouse::query()->find($this->input('warehouse_id'));
            if ($warehouse && ! $warehouse->is_leaf) {
                $validator->errors()->add('warehouse_id', 'Stock can only be posted to a leaf warehouse.');
            }
        });
    }
}
