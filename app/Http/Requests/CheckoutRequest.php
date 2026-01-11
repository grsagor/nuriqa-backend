<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'billing_first_name' => 'required|string|max:255',
            'billing_last_name' => 'required|string|max:255',
            'billing_email' => 'required|email|max:255',
            'billing_phone' => 'required|string|max:255',
            'donate_anonymous' => 'boolean',
            'payment_method' => 'required|in:card,paypal,bank,cod',
            'keep_updated' => 'boolean',
            'agree_terms' => 'required|accepted',
            'cart_items' => 'required|array|min:1',
            'cart_items.*.id' => 'required|integer',
            'cart_items.*.quantity' => 'required|integer|min:1',
            'payment_intent_id' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'billing_first_name.required' => 'First name is required',
            'billing_last_name.required' => 'Last name is required',
            'billing_email.required' => 'Email is required',
            'billing_email.email' => 'Please provide a valid email address',
            'billing_phone.required' => 'Phone number is required',
            'payment_method.required' => 'Payment method is required',
            'payment_method.in' => 'Invalid payment method selected',
            'agree_terms.required' => 'You must agree to the terms and conditions',
            'agree_terms.accepted' => 'You must agree to the terms and conditions',
            'cart_items.required' => 'Cart items are required',
            'cart_items.min' => 'Cart must contain at least one item',
        ];
    }
}
