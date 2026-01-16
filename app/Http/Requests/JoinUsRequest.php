<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JoinUsRequest extends FormRequest
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
        $type = $this->input('type');
        $baseRules = [
            'type' => 'required|in:model,volunteer',
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:255',
            'age' => 'nullable|integer|min:1|max:120',
            'gender' => 'nullable|in:male,female,other',
            'nationality' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'apartment_suite_unit' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
        ];

        if ($type === 'model') {
            $baseRules = array_merge($baseRules, [
                'height' => 'nullable|string|max:50',
                'weight' => 'nullable|string|max:50',
                'comfort_preferences' => 'nullable|array',
                'comfort_preferences.*' => 'string',
                'model_experiences' => 'nullable|array',
                'model_experiences.*' => 'string',
                'model_motivation' => 'nullable|string',
                'images' => 'nullable|array',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
                'model_images' => 'nullable|array',
                'model_images.*' => 'string',
                'agreements' => 'nullable|array',
            ]);
        } elseif ($type === 'volunteer') {
            $baseRules = array_merge($baseRules, [
                'areas_of_interest' => 'nullable|array',
                'areas_of_interest.*' => 'string',
                'volunteer_experiences' => 'nullable|string',
                'availability' => 'nullable|array',
                'availability.*' => 'string',
                'commitment_level' => 'nullable|array',
                'commitment_level.*' => 'string',
                'volunteer_motivation' => 'nullable|string',
                'cv' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
                'cv_path' => 'nullable|string|max:500',
                'agreements' => 'nullable|array',
            ]);
        }

        return $baseRules;
    }
}
