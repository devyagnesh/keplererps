<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for purchase bill approval, including mismatch override reason (US-M07-04).
 */
class PurchaseBillApproveRequest extends FormRequest
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
            'mismatch_reason' => ['nullable', 'string', 'min:5', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mismatch_reason.min' => 'Give a meaningful reason for approving outside tolerance.',
        ];
    }
}
