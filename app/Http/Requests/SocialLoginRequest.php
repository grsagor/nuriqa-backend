<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SocialLoginRequest extends FormRequest
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
            'id_token' => ['required', 'string'],
            'provider' => ['nullable', 'string', 'in:google,apple'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_token.required' => 'A Firebase ID token is required.',
            'provider.in' => 'Provider must be google or apple.',
        ];
    }
}
