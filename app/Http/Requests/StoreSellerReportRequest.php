<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSellerReportRequest extends FormRequest
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
            'reported_user_id' => ['required', 'integer', 'exists:users,id'],
            'reason' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reported_user_id.required' => 'Please specify which seller you are reporting.',
            'reported_user_id.exists' => 'The seller could not be found.',
            'reason.required' => 'Please choose a reason for your report.',
        ];
    }
}
