<?php

namespace App\Http\Requests\Admin;

use App\Enums\QcDisposition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates QC inspection update / complete payloads.
 */
class QcInspectionRequest extends FormRequest
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
        $isComplete = $this->routeIs('admin.qc-inspections.complete');

        return [
            'sample_size' => ['nullable', 'numeric', 'gt:0'],
            'sample_override_reason' => ['nullable', 'string', 'max:255'],
            'accepted_qty' => [$isComplete ? 'required' : 'nullable', 'numeric', 'min:0'],
            'rejected_qty' => [$isComplete ? 'required' : 'nullable', 'numeric', 'min:0'],
            'rework_qty' => [$isComplete ? 'required' : 'nullable', 'numeric', 'min:0'],
            'disposition' => [$isComplete ? 'required' : 'nullable', Rule::in(QcDisposition::values())],
            'deviation_note' => ['nullable', 'string', 'max:2000'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'readings' => [$isComplete ? 'required' : 'nullable', 'array'],
            'readings.*.id' => ['required', 'integer', 'exists:qc_inspection_readings,id'],
            'readings.*.numeric_value' => ['nullable', 'numeric'],
            'readings.*.pass_fail_value' => ['nullable', Rule::in(['pass', 'fail'])],
            'readings.*.text_value' => ['nullable', 'string', 'max:500'],
        ];
    }
}
