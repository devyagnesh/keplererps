<?php

namespace App\Http\Requests\Admin;

use App\Enums\InspectionType;
use App\Enums\QcParameterType;
use App\Enums\SamplingPlanType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates QC template create/update payloads.
 */
class QcTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'code' => $this->filled('code') ? strtoupper(trim((string) $this->input('code'))) : null,
            'item_id' => $this->filled('item_id') ? $this->input('item_id') : null,
            'category_id' => $this->filled('category_id') ? $this->input('category_id') : null,
            'parameters' => array_values(array_filter(
                $this->input('parameters', []),
                fn ($row) => is_array($row) && ! empty($row['name'])
            )),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('qc_template')?->id;

        return [
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('qc_templates', 'code')->ignore($id),
            ],
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'inspection_type' => ['required', Rule::in(InspectionType::values())],
            'item_id' => ['nullable', 'integer', 'exists:items,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sampling_plan' => ['required', Rule::in(SamplingPlanType::values())],
            'sampling_value' => [
                'nullable',
                'numeric',
                'gt:0',
                Rule::requiredIf(fn () => in_array($this->input('sampling_plan'), [
                    SamplingPlanType::Fixed->value,
                    SamplingPlanType::Percentage->value,
                ], true)),
            ],
            'is_active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'parameters' => ['required', 'array', 'min:1'],
            'parameters.*.name' => ['required', 'string', 'max:150'],
            'parameters.*.parameter_type' => ['required', Rule::in(QcParameterType::values())],
            'parameters.*.uom' => ['nullable', 'string', 'max:30'],
            'parameters.*.min_value' => ['nullable', 'numeric'],
            'parameters.*.max_value' => ['nullable', 'numeric', 'gte:parameters.*.min_value'],
            'parameters.*.target_value' => ['nullable', 'numeric'],
            'parameters.*.is_critical' => ['nullable', 'boolean'],
            'parameters.*.test_method' => ['nullable', 'string', 'max:150'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'parameters.required' => 'Add at least one QC parameter.',
            'sampling_value.required' => 'Sampling value is required for fixed / percentage plans.',
        ];
    }
}
