<?php

namespace App\Http\Requests\Admin;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates asset / work centre create and update.
 */
class WorkCentreRequest extends FormRequest
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
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('work_centre')?->id;
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'code' => [
                Rule::requiredIf(! $isUpdate),
                'nullable',
                'string',
                'min:3',
                'max:20',
                Rule::unique('work_centres', 'code')->ignore($id),
            ],
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'asset_type' => ['required', Rule::in(AssetType::values())],
            'status' => ['nullable', Rule::in(AssetStatus::values())],
            'make_model' => ['nullable', 'string', 'max:150'],
            'serial_no' => ['nullable', 'string', 'max:100'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_value' => ['nullable', 'numeric', 'min:0'],
            'location' => ['nullable', 'string', 'max:150'],
            'department' => ['nullable', 'string', 'max:100'],
            'capacity' => ['nullable', 'string', 'max:100'],
            'machine_rate_per_hour' => ['nullable', 'numeric', 'min:0'],
            'labour_rate_per_hour' => ['nullable', 'numeric', 'min:0'],
            'cavity_count' => ['nullable', 'integer', 'min:1', 'max:128'],
            'cycle_time_seconds' => ['nullable', 'numeric', 'min:0'],
            'life_cycles' => ['nullable', 'integer', 'min:1'],
            'service_interval_days' => ['nullable', 'integer', 'min:1'],
            'service_interval_hours' => ['nullable', 'numeric', 'min:0'],
            'service_interval_cycles' => ['nullable', 'integer', 'min:1'],
            'next_service_due_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
