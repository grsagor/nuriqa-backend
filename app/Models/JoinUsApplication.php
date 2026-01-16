<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JoinUsApplication extends Model
{
    protected $fillable = [
        'type',
        'full_name',
        'email',
        'phone',
        'age',
        'gender',
        'nationality',
        'address',
        'apartment_suite_unit',
        'city',
        'postal_code',
        'height',
        'weight',
        'comfort_preferences',
        'model_experiences',
        'model_motivation',
        'model_images',
        'areas_of_interest',
        'volunteer_experiences',
        'availability',
        'commitment_level',
        'volunteer_motivation',
        'cv_path',
        'agreements',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'comfort_preferences' => 'array',
            'model_experiences' => 'array',
            'model_images' => 'array',
            'areas_of_interest' => 'array',
            'availability' => 'array',
            'commitment_level' => 'array',
            'agreements' => 'array',
            'age' => 'integer',
        ];
    }
}
