<?php

namespace App\Http\Requests\Admin;

use App\Enums\RejectionDisposition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for production entry (M09).
 */
class ProductionEntryRequest extends FormRequest
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
            'work_order_id' => ['required', 'integer', 'exists:work_orders,id'],
            'document_date' => ['required', 'date', 'before_or_equal:today'],
            'good_quantity' => ['nullable', 'numeric', 'min:0'],
            'rejected_quantity' => ['nullable', 'numeric', 'min:0'],
            'defect_reason_id' => ['nullable', 'integer', 'exists:defect_reasons,id'],
            'rejection_disposition' => ['nullable', Rule::in(RejectionDisposition::values())],
            'downgrade_item_id' => ['nullable', 'integer', 'exists:items,id'],
            'batch_no' => ['nullable', 'string', 'max:50'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'downtime_minutes' => ['nullable', 'integer', 'min:0'],
            'downtime_reason' => ['nullable', 'string', 'max:100'],
            'machine_hours' => ['nullable', 'numeric', 'min:0'],
            'labour_hours' => ['nullable', 'numeric', 'min:0'],
            'operator_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'post_immediately' => ['nullable', 'boolean'],
        ];
    }
}
