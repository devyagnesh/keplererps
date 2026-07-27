<?php

namespace App\Http\Requests\Admin;

use App\Rules\Gstin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates transporter create/update.
 */
class TransporterRequest extends FormRequest
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
            'gstin' => $this->filled('gstin') ? strtoupper((string) $this->input('gstin')) : null,
            'email' => $this->filled('email') ? strtolower(trim((string) $this->input('email'))) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('transporter')?->id;

        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('transporters', 'code')->ignore($id)->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:150'],
            'gstin' => ['nullable', 'string', 'size:15', new Gstin, Rule::unique('transporters', 'gstin')->ignore($id)->whereNull('deleted_at')],
            'phone' => ['nullable', 'string', 'regex:/^\+?[0-9]{10,15}$/'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:250'],
            'vehicle_types' => ['nullable', 'string', 'max:150'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
