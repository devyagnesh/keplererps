<?php

namespace App\Http\Requests\Admin;

use App\Enums\MaintenanceOrderType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates maintenance order create / update.
 */
class MaintenanceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isCreate = $this->isMethod('POST');

        return [
            'document_date' => ['required', 'date'],
            'order_type' => [$isCreate ? 'required' : 'nullable', Rule::in(MaintenanceOrderType::values())],
            'work_centre_id' => [$isCreate ? 'required' : 'nullable', 'integer', 'exists:work_centres,id'],
            'reason' => ['nullable', 'string', 'max:255'],
            'action_taken' => ['nullable', 'string', 'max:5000'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'parts' => ['nullable', 'array'],
            'parts.*.item_id' => ['required_with:parts', 'integer', 'exists:items,id'],
            'parts.*.warehouse_id' => ['required_with:parts', 'integer', 'exists:warehouses,id'],
            'parts.*.quantity' => ['required_with:parts', 'numeric', 'gt:0'],
        ];
    }
}
