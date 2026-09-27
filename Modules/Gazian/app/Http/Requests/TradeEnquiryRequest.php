<?php

namespace Modules\Gazian\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TradeEnquiryRequest extends FormRequest
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
            'interest' => 'required|string|max:255',
            'business_type' => 'required|string|max:255',
            'explore' => 'required|array|min:1',
            'explore.*' => 'required|string|max:100',
            'country' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'interest.required' => 'Please tell us what you are interested in',
            'business_type.required' => 'Please select your business type',
            'explore.required' => 'Please select at least one product to explore',
            'explore.min' => 'Please select at least one product to explore',
            'country.required' => 'Country is required',
            'city.required' => 'City is required',
            'name.required' => 'Name is required',
            'email.required' => 'Email is required',
            'email.email' => 'Please provide a valid email address',
        ];
    }
}
