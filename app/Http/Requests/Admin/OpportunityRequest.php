<?php

namespace App\Http\Requests\Admin;

use App\Enums\OpportunityStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for opportunity create and edit (M05).
 */
class OpportunityRequest extends FormRequest
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
            'opportunity_date' => ['required', 'date'],
            'title' => ['required', 'string', 'min:3', 'max:150'],
            'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
            'party_id' => ['nullable', 'integer', 'exists:parties,id'],
            'stage' => [$this->isMethod('POST') ? 'nullable' : 'prohibited', Rule::in(OpportunityStage::values())],
            'expected_value' => ['nullable', 'numeric', 'min:0'],
            'probability_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'expected_close_date' => ['nullable', 'date'],
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
            'title.required' => 'Give the opportunity a short title.',
            'stage.prohibited' => 'Use the stage actions to move an opportunity.',
        ];
    }
}
