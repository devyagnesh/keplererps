<?php

namespace App\Http\Requests\Admin;

use App\Enums\BomIssueMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for BOM create/update (M04).
 */
class BomRequest extends FormRequest
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
        $isUpdate = $this->route('bom') !== null;

        return [
            'item_id' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'exists:items,id'],
            'output_quantity' => ['required', 'numeric', 'gt:0'],
            'valid_from' => ['required', 'date'],
            'valid_to' => ['nullable', 'date', 'after:valid_from'],
            'is_active' => ['nullable', 'boolean'],
            'overhead_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'components' => ['required', 'array', 'min:1'],
            'components.*.component_item_id' => ['required', 'integer', 'exists:items,id', 'different:item_id'],
            'components.*.quantity' => ['required', 'numeric', 'gt:0'],
            'components.*.uom_id' => ['required', 'integer', 'exists:uoms,id'],
            'components.*.wastage_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'components.*.is_critical' => ['nullable', 'boolean'],
            'components.*.issue_method' => ['required', Rule::in(BomIssueMethod::values())],
            'components.*.operation_sequence' => ['nullable', 'integer', 'min:1'],
            'operations' => ['nullable', 'array'],
            'operations.*.sequence' => ['required_with:operations', 'integer', 'min:1'],
            'operations.*.manufacturing_operation_id' => ['required_with:operations', 'integer', 'exists:manufacturing_operations,id'],
            'operations.*.work_centre_id' => ['nullable', 'integer', 'exists:work_centres,id'],
            'operations.*.setup_time_minutes' => ['nullable', 'numeric', 'min:0'],
            'operations.*.run_time_per_unit_minutes' => ['required_with:operations', 'numeric', 'gt:0'],
            'operations.*.machine_rate_per_hour' => ['nullable', 'numeric', 'min:0'],
            'operations.*.labour_rate_per_hour' => ['nullable', 'numeric', 'min:0'],
            'operations.*.operators_required' => ['nullable', 'integer', 'min:0', 'max:20'],
            'operations.*.is_outsourced' => ['nullable', 'boolean'],
            'operations.*.vendor_id' => ['nullable', 'integer', 'exists:parties,id'],
            'operations.*.outsourced_rate' => ['nullable', 'numeric', 'min:0'],
            'operations.*.quality_check_required' => ['nullable', 'boolean'],
            'outputs' => ['nullable', 'array'],
            'outputs.*.item_id' => ['required_with:outputs', 'integer', 'exists:items,id'],
            'outputs.*.expected_quantity' => ['required_with:outputs', 'numeric', 'gt:0'],
            'outputs.*.uom_id' => ['required_with:outputs', 'integer', 'exists:uoms,id'],
            'outputs.*.cost_allocation_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'outputs.*.output_type' => ['nullable', Rule::in(['by_product', 'scrap'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'components.required' => 'Add at least one component.',
            'components.*.component_item_id.different' => 'A component cannot be the finished item.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'overhead_percent' => $this->input('overhead_percent', 0),
        ]);
    }
}
