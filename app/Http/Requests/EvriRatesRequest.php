<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EvriRatesRequest extends FormRequest
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
            'from_postcode' => ['required', 'string', 'max:10'],
            'to_postcode' => ['required', 'string', 'max:10'],
            'weight_g' => ['required', 'integer', 'min:1', 'max:30000'],
            'length_cm' => ['required', 'integer', 'min:1', 'max:100'],
            'width_cm' => ['required', 'integer', 'min:1', 'max:100'],
            'height_cm' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'from_postcode.required' => 'Origin postcode is required.',
            'to_postcode.required' => 'Destination postcode is required.',
            'weight_g.required' => 'Package weight in grams is required.',
        ];
    }
}
