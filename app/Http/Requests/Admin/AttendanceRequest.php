<?php

namespace App\Http\Requests\Admin;

use App\Enums\AttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for the daily attendance sheet (M14).
 */
class AttendanceRequest extends FormRequest
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
        return [
            'attendance_date' => ['required', 'date', 'before_or_equal:today'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.employee_id' => ['required', 'integer', 'exists:employees,id'],
            'rows.*.status' => ['required', Rule::in(AttendanceStatus::values())],
            'rows.*.shift_id' => ['nullable', 'integer', 'exists:shifts,id'],
            'rows.*.worked_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'rows.*.overtime_hours' => ['nullable', 'numeric', 'min:0', 'max:12'],
            'rows.*.remarks' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'attendance_date.before_or_equal' => 'Attendance cannot be marked for a future date.',
            'rows.required' => 'There are no employees to mark for this date.',
            'rows.*.status.required' => 'Select an attendance status for every employee.',
        ];
    }
}
