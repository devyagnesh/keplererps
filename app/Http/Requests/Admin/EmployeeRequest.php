<?php

namespace App\Http\Requests\Admin;

use App\Enums\EmploymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for the employee master (M14).
 */
class EmployeeRequest extends FormRequest
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
        $id = $this->route('employee')?->id;

        return [
            'full_name' => ['required', 'string', 'min:2', 'max:120'],
            'designation' => ['nullable', 'string', 'max:80'],
            'department' => ['nullable', 'string', 'max:80'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'shift_id' => ['nullable', 'integer', 'exists:shifts,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id', Rule::unique('employees', 'user_id')->ignore($id)->whereNull('deleted_at')],
            'mobile' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:120'],
            'date_of_joining' => ['required', 'date'],
            'date_of_exit' => ['nullable', 'date', 'after_or_equal:date_of_joining'],
            'status' => ['required', Rule::in(EmploymentStatus::values())],
            'monthly_gross' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'basic_percent' => ['required', 'numeric', 'min:1', 'max:100'],
            'fixed_deduction' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'overtime_rate_per_hour' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'bank_account_no' => ['nullable', 'string', 'max:30'],
            'ifsc_code' => ['nullable', 'string', 'max:15'],
            'pan' => ['nullable', 'string', 'max:10'],
            'uan' => ['nullable', 'string', 'max:20'],
            'pf_number' => ['nullable', 'string', 'max:30'],
            'esi_number' => ['nullable', 'string', 'max:30'],
            'aadhaar_last4' => ['nullable', 'digits:4'],
            'biometric_code' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('employees', 'biometric_code')->ignore($id)->whereNull('deleted_at'),
            ],
            'remarks' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Employee name is required.',
            'date_of_joining.required' => 'Joining date is required.',
            'monthly_gross.required' => 'Monthly gross salary is required.',
            'basic_percent.min' => 'Basic pay must be at least 1% of gross.',
            'user_id.unique' => 'This login is already linked to another employee.',
        ];
    }
}
