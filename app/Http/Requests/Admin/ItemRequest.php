<?php

namespace App\Http\Requests\Admin;

use App\Enums\ItemType;
use App\Enums\TrackingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates item create/update payloads including nested UOM, warehouse, and substitute rows.
 */
class ItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_purchasable' => $this->boolean('is_purchasable'),
            'is_sellable' => $this->boolean('is_sellable'),
            'is_manufacturable' => $this->boolean('is_manufacturable'),
            'requires_inspection' => $this->boolean('requires_inspection'),
            'expiry_tracking' => $this->boolean('expiry_tracking'),
            'item_code' => $this->filled('item_code') ? strtoupper(trim((string) $this->input('item_code'))) : null,
            'barcode' => $this->filled('barcode') ? trim((string) $this->input('barcode')) : null,
            'uom_conversions' => array_values(array_filter(
                $this->input('uom_conversions', []),
                fn ($row) => is_array($row) && (! empty($row['from_uom_id']) || ! empty($row['to_uom_id']) || ! empty($row['factor']))
            )),
            'warehouse_settings' => array_values(array_filter(
                $this->input('warehouse_settings', []),
                fn ($row) => is_array($row) && ! empty($row['warehouse_id'])
            )),
            'substitutes' => array_values(array_filter(
                $this->input('substitutes', []),
                fn ($row) => is_array($row) && ! empty($row['substitute_item_id'])
            )),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('item')?->id;

        return [
            'item_code' => [
                Rule::requiredIf(fn () => $this->isMethod('PUT') || $this->isMethod('PATCH')),
                'nullable',
                'string',
                'max:30',
                Rule::unique('items', 'item_code')->ignore($id),
            ],
            'item_name' => ['required', 'string', 'min:2', 'max:150'],
            'item_type' => ['required', Rule::in(ItemType::values())],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'sub_category_id' => ['nullable', 'integer', 'exists:categories,id', 'different:category_id'],
            'stock_uom_id' => ['required', 'integer', 'exists:uoms,id'],
            'purchase_uom_id' => ['nullable', 'integer', 'exists:uoms,id'],
            'sales_uom_id' => ['nullable', 'integer', 'exists:uoms,id'],
            'hsn_code_id' => ['required', 'integer', 'exists:hsn_codes,id'],
            'gst_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'cess_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tracking_type' => ['required', Rule::in(TrackingType::values())],
            'expiry_tracking' => ['required', 'boolean'],
            'shelf_life_days' => ['nullable', 'integer', 'min:1', 'max:3650', 'required_if:expiry_tracking,1,true'],
            'standard_cost' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'minimum_selling_price' => ['nullable', 'numeric', 'min:0'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'max_stock' => ['nullable', 'numeric', 'min:0', 'gte:min_stock'],
            'lead_time_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'default_warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'weight_per_unit' => ['nullable', 'numeric', 'min:0'],
            'barcode' => ['nullable', 'string', 'max:64', Rule::unique('items', 'barcode')->ignore($id)],
            'is_purchasable' => ['required', 'boolean'],
            'is_sellable' => ['required', 'boolean'],
            'is_manufacturable' => ['required', 'boolean'],
            'requires_inspection' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:2000'],

            'uom_conversions' => ['nullable', 'array'],
            'uom_conversions.*.from_uom_id' => ['required_with:uom_conversions.*.to_uom_id', 'integer', 'exists:uoms,id'],
            'uom_conversions.*.to_uom_id' => ['required_with:uom_conversions.*.from_uom_id', 'integer', 'exists:uoms,id', 'different:uom_conversions.*.from_uom_id'],
            'uom_conversions.*.factor' => ['required_with:uom_conversions.*.from_uom_id', 'numeric', 'gt:0'],

            'warehouse_settings' => ['nullable', 'array'],
            'warehouse_settings.*.warehouse_id' => ['required', 'integer', 'exists:warehouses,id', 'distinct'],
            'warehouse_settings.*.reorder_level' => ['nullable', 'numeric', 'min:0'],
            'warehouse_settings.*.reorder_qty' => ['nullable', 'numeric', 'min:0'],
            'warehouse_settings.*.min_stock' => ['nullable', 'numeric', 'min:0'],
            'warehouse_settings.*.max_stock' => ['nullable', 'numeric', 'min:0'],

            'substitutes' => ['nullable', 'array'],
            'substitutes.*.substitute_item_id' => ['required', 'integer', 'exists:items,id', 'distinct'],
            'substitutes.*.conversion_ratio' => ['nullable', 'numeric', 'gt:0'],
            'substitutes.*.is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'shelf_life_days.required_if' => 'Shelf life is required when expiry tracking is enabled.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $item = $this->route('item');

            if ($item?->has_transactions && $this->filled('item_code') && $this->input('item_code') !== $item->item_code) {
                $validator->errors()->add('item_code', 'Item code cannot be changed after the first transaction.');
            }

            if ($item?->has_stock) {
                if ($this->input('item_type') !== $item->item_type->value) {
                    $validator->errors()->add('item_type', 'Item type is locked after stock exists.');
                }
                if ((int) $this->input('stock_uom_id') !== (int) $item->stock_uom_id) {
                    $validator->errors()->add('stock_uom_id', 'Stock UOM is locked after stock exists.');
                }
                if ($this->input('tracking_type') !== $item->tracking_type->value) {
                    $validator->errors()->add('tracking_type', 'Tracking type is locked after stock exists.');
                }
            }

            $selling = $this->input('selling_price');
            $minimum = $this->input('minimum_selling_price');
            if ($selling !== null && $minimum !== null && $selling !== '' && $minimum !== '' && (float) $minimum > (float) $selling) {
                $validator->errors()->add('minimum_selling_price', 'Minimum selling price cannot exceed selling price.');
            }

            foreach ($this->input('warehouse_settings', []) as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $min = $row['min_stock'] ?? null;
                $max = $row['max_stock'] ?? null;
                if ($min !== null && $min !== '' && $max !== null && $max !== '' && (float) $max < (float) $min) {
                    $validator->errors()->add("warehouse_settings.{$index}.max_stock", 'Max stock must be greater than or equal to min stock.');
                }
            }

            $itemId = $item?->id;
            foreach ($this->input('substitutes', []) as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }
                if ($itemId !== null && (int) ($row['substitute_item_id'] ?? 0) === (int) $itemId) {
                    $validator->errors()->add("substitutes.{$index}.substitute_item_id", 'An item cannot substitute itself.');
                }
            }
        });
    }
}
