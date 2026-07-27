<?php

namespace App\Http\Requests\Admin;

use App\Enums\AdjustmentDirection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates stock adjustment create/update.
 */
class StockAdjustmentRequest extends FormRequest
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
            'reason' => ['required', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'items.*.batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'items.*.direction' => ['required', Rule::in(AdjustmentDirection::values())],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.rate' => ['nullable', 'numeric', 'min:0'],
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
