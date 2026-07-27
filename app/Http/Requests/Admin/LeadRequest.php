<?php

namespace App\Http\Requests\Admin;

use App\Enums\LeadSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for lead capture and edit (M05).
 */
class LeadRequest extends FormRequest
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
            'lead_date' => ['required', 'date'],
            'company_name' => ['required', 'string', 'min:2', 'max:150'],
            'contact_person' => ['required', 'string', 'min:2', 'max:120'],
            'mobile' => ['required', 'string', 'min:10', 'max:20'],
            'email' => ['nullable', 'email', 'max:120'],
            'city' => ['nullable', 'string', 'max:80'],
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'industry' => ['nullable', 'string', 'max:100'],
            'source' => ['required', Rule::in(LeadSource::values())],
            'requirement' => ['nullable', 'string', 'max:2000'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'next_follow_up_date' => ['nullable', 'date'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_name.required' => 'Company name is required.',
            'contact_person.required' => 'Contact person is required.',
            'mobile.required' => 'Mobile number is required.',
            'source.required' => 'Select where this lead came from.',
        ];
    }
}
