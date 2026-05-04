<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordSendOtpRequest extends FormRequest
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
            'identifier' => ['nullable', 'string', 'max:255'],
            'phone_country_code' => ['nullable', 'string', 'regex:/^\+[1-9]\d{0,3}$/'],
            'phone_number' => ['nullable', 'string', 'regex:/^\d{5,20}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_country_code.regex' => 'Enter a valid country code (e.g. +44).',
            'phone_number.regex' => 'Enter digits only for the phone number.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $hasPair = $this->filled('phone_country_code') && $this->filled('phone_number');
            $hasIdentifier = $this->filled('identifier') && trim((string) $this->input('identifier')) !== '';

            if (! $hasPair && ! $hasIdentifier) {
                $validator->errors()->add('identifier', 'Enter your email or phone number.');
            }

            if ($hasPair && $hasIdentifier) {
                $validator->errors()->add('identifier', 'Use either email / phone or the UK phone fields, not both.');
            }

            if ($hasIdentifier && ! $hasPair) {
                $id = trim((string) $this->input('identifier'));
                if (filter_var($id, FILTER_VALIDATE_EMAIL)) {
                    return;
                }
                if (strlen($id) < 8) {
                    $validator->errors()->add('identifier', 'Enter a valid email or international phone number.');
                }
            }
        });
    }
}
