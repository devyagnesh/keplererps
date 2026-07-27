<?php

namespace App\Http\Requests\Admin;

use App\Enums\DocumentSeriesType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates document number series create/update.
 */
class DocumentNumberSeriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'include_fy_code' => $this->boolean('include_fy_code'),
            'reset_yearly' => $this->boolean('reset_yearly'),
            'is_active' => $this->boolean('is_active'),
            'prefix' => $this->filled('prefix') ? strtoupper(trim((string) $this->input('prefix'))) : null,
            'financial_year_id' => $this->filled('financial_year_id') ? $this->input('financial_year_id') : null,
            'branch_id' => $this->filled('branch_id') ? $this->input('branch_id') : null,
            'suffix' => $this->filled('suffix') ? trim((string) $this->input('suffix')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('document_number_series')?->id
            ?? $this->route('document_series')?->id;

        return [
            'document_type' => [
                'required',
                Rule::in(DocumentSeriesType::values()),
                Rule::unique('document_number_series', 'document_type')
                    ->ignore($id)
                    ->where(fn ($q) => $q
                        ->where('financial_year_id', $this->input('financial_year_id'))
                        ->where('branch_id', $this->input('branch_id'))
                        ->whereNull('deleted_at')),
            ],
            'financial_year_id' => ['nullable', 'integer', 'exists:financial_years,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'prefix' => ['required', 'string', 'max:20'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'separator' => ['required', 'string', 'max:5'],
            'padding' => ['required', 'integer', 'min:1', 'max:10'],
            'start_number' => ['required', 'integer', 'min:1'],
            'include_fy_code' => ['required', 'boolean'],
            'reset_yearly' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
