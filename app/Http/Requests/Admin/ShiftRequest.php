<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for the shift master (M14).
 */
class ShiftRequest extends FormRequest
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
        $id = $this->route('shift')?->id;

        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('shifts', 'code')->ignore($id)->whereNull('deleted_at')],
            'name' => ['required', 'string', 'min:2', 'max:60'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'different:start_time'],
            'break_minutes' => ['nullable', 'integer', 'min:0', 'max:480'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Shift code is required.',
            'code.unique' => 'This shift code is already taken.',
            'end_time.different' => 'The end time must differ from the start time.',
        ];
    }
}
