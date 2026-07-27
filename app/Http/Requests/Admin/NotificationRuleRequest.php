<?php

namespace App\Http\Requests\Admin;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEvent;
use App\Enums\NotificationRecipientType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for notification rule create/update.
 */
class NotificationRuleRequest extends FormRequest
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
        $ruleId = $this->route('notification_rule')?->id;

        return [
            'code' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('notification_rules', 'code')->ignore($ruleId)->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'event' => ['required', Rule::enum(NotificationEvent::class)],
            'channel' => ['required', Rule::enum(NotificationChannel::class)],
            'recipient_type' => ['required', Rule::enum(NotificationRecipientType::class)],
            'recipient_value' => ['required', 'string', 'max:100'],
            'subject_template' => ['required', 'string', 'max:200'],
            'body_template' => ['required', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Rule name is required.',
            'event.required' => 'Select a business event.',
            'recipient_value.required' => 'Select who should receive this notification.',
            'subject_template.required' => 'Subject template is required.',
            'body_template.required' => 'Body template is required.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
