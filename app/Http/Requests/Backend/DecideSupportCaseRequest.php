<?php

namespace App\Http\Requests\Backend;

use App\Models\SupportCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideSupportCaseRequest extends FormRequest
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
            'decision' => ['required', 'string', 'max:5000'],
            'status' => [
                'required',
                'string',
                Rule::in([
                    SupportCase::STATUS_RESOLVED,
                    SupportCase::STATUS_CLOSED,
                    SupportCase::STATUS_AWAITING_CUSTOMER,
                    SupportCase::STATUS_IN_PROGRESS,
                ]),
            ],
            'financial_outcome' => ['nullable', 'numeric'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
            'message' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
