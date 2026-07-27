<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates HSN / SAC master create and update.
 */
class HsnCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $code = preg_replace('/\D+/', '', (string) $this->input('code', '')) ?? '';

        $this->merge([
            'code' => $code,
            'code_type' => strtolower((string) $this->input('code_type', 'hsn')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('hsn_code')?->id;

        return [
            'code' => ['required', 'string', 'min:4', 'max:8', Rule::unique('hsn_codes', 'code')->ignore($id)],
            'code_type' => ['required', Rule::in(['hsn', 'sac'])],
            'description' => ['required', 'string', 'max:255'],
            'default_gst_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $code = (string) $this->input('code');
            $type = (string) $this->input('code_type');

            if ($type === 'hsn' && ! in_array(strlen($code), [4, 6, 8], true)) {
                $validator->errors()->add('code', 'HSN code must be 4, 6, or 8 digits.');
            }

            if ($type === 'sac' && strlen($code) !== 6) {
                $validator->errors()->add('code', 'SAC code must be exactly 6 digits.');
            }
        });
    }
}
