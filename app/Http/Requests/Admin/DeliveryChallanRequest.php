<?php

namespace App\Http\Requests\Admin;

use App\Enums\TransportMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for delivery challan create/update (M12).
 */
class DeliveryChallanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('vehicle_number')) {
            $this->merge([
                'vehicle_number' => strtoupper(preg_replace('/\s+/', '', (string) $this->input('vehicle_number')) ?? ''),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $vehicleRules = ['nullable', 'string', 'max:20'];
        if ($this->input('transport_mode') === TransportMode::Road->value) {
            $vehicleRules = ['required', 'string', 'max:20', 'regex:/^[A-Z]{2}[0-9]{1,2}[A-Z]{0,3}[0-9]{4}$/'];
        }

        $rules = [
            'document_date' => ['required', 'date'],
            'transport_mode' => ['required', Rule::in(TransportMode::values())],
            'vehicle_number' => $vehicleRules,
            'transporter_id' => ['nullable', 'integer', 'exists:transporters,id'],
            'transporter_gstin' => ['nullable', 'string', 'max:15'],
            'lr_number' => ['nullable', 'string', 'max:30'],
            'lr_date' => ['nullable', 'date'],
            'distance_km' => ['nullable', 'integer', 'min:1', 'max:4000'],
            'driver_name' => ['nullable', 'string', 'max:100'],
            'driver_mobile' => ['nullable', 'digits:10'],
            'number_of_packages' => ['required', 'integer', 'min:1'],
            'gross_weight' => ['nullable', 'numeric', 'min:0'],
            'net_weight' => ['nullable', 'numeric', 'min:0'],
            'eway_bill_number' => ['nullable', 'digits:12'],
            'expected_delivery_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sales_order_item_id' => ['required', 'integer', 'exists:sales_order_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
        ];

        if ($this->isMethod('post')) {
            $rules['sales_order_id'] = ['required', 'integer', 'exists:sales_orders,id'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'vehicle_number.regex' => 'Vehicle number must match the Indian format (e.g. GJ01AB1234).',
        ];
    }
}
