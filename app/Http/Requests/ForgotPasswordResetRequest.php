<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordResetRequest extends FormRequest
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
            'reset_token' => ['required', 'string', 'size:64'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'reset_token.size' => 'The reset token is invalid.',
        ];
    }
}
