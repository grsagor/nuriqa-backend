<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutShippingRatesRequest extends FormRequest
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
            'shipping_postcode' => ['required', 'string', 'max:16'],
            'cart_item_ids' => ['required', 'array', 'min:1'],
            'cart_item_ids.*' => ['integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'shipping_postcode.required' => 'Delivery postcode is required to calculate shipping.',
            'cart_item_ids.required' => 'Cart items are required to calculate shipping.',
            'cart_item_ids.min' => 'At least one cart item is required.',
        ];
    }
}
