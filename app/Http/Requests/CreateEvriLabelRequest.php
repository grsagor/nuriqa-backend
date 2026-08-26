<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateEvriLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'address_to' => ['required', 'array'],
            'address_to.name' => ['required', 'string', 'max:100'],
            'address_to.address_line_1' => ['required', 'string', 'max:32'],
            'address_to.address_line_2' => ['nullable', 'string', 'max:32'],
            'address_to.city' => ['required', 'string', 'max:32'],
            'address_to.county' => ['nullable', 'string', 'max:32'],
            'address_to.postcode' => ['required', 'string', 'max:10'],
            'address_to.country' => ['nullable', 'string', 'size:2'],
            'address_to.phone' => ['nullable', 'string', 'max:20'],
            'address_to.email' => ['nullable', 'email', 'max:80'],
            'address_from' => ['required', 'array'],
            'address_from.name' => ['required', 'string', 'max:100'],
            'address_from.address_line_1' => ['required', 'string', 'max:100'],
            'address_from.city' => ['required', 'string', 'max:50'],
            'address_from.postcode' => ['required', 'string', 'max:10'],
            'address_from.country' => ['nullable', 'string', 'size:2'],
            'address_from.phone' => ['nullable', 'string', 'max:20'],
            'address_from.email' => ['nullable', 'email', 'max:100'],
            'package_details' => ['required', 'array'],
            'package_details.weight_g' => ['required', 'integer', 'min:1', 'max:30000'],
            'package_details.length_cm' => ['required', 'integer', 'min:1', 'max:100'],
            'package_details.width_cm' => ['required', 'integer', 'min:1', 'max:100'],
            'package_details.height_cm' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'address_to.required' => 'Delivery address is required.',
            'address_to.name.required' => 'Recipient name is required.',
            'address_to.address_line_1.required' => 'Delivery address line 1 is required.',
            'address_to.address_line_1.max' => 'EVRi address line 1 cannot exceed 32 characters.',
            'address_to.city.required' => 'Delivery city is required.',
            'address_to.postcode.required' => 'Delivery postcode is required.',
            'address_from.required' => 'Sender address is required.',
            'package_details.required' => 'Package details are required.',
            'package_details.weight_g.required' => 'Package weight in grams is required.',
        ];
    }
}
