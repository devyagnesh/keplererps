<?php

namespace App\Http\Requests\Admin;

use App\Enums\FollowUpMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for logging a CRM follow-up (M05).
 */
class CrmFollowUpRequest extends FormRequest
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
            'follow_up_date' => ['required', 'date'],
            'mode' => ['required', Rule::in(FollowUpMode::values())],
            'summary' => ['required', 'string', 'min:3', 'max:2000'],
            'outcome' => ['nullable', 'string', 'max:255'],
            'next_follow_up_date' => ['nullable', 'date', 'after_or_equal:follow_up_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'summary.required' => 'Describe what was discussed.',
            'next_follow_up_date.after_or_equal' => 'The next follow-up cannot be before this one.',
        ];
    }
}
