<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'required', 'in:pending,created,in_transit,delivered,failed,cancelled'],
            'tracking_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'label_url' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'shipping_fee' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Invalid shipment status.',
            'shipping_fee.min' => 'Shipping fee cannot be negative.',
        ];
    }
}
