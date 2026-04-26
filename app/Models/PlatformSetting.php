<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $fillable = [
        'fee_percentage',
    ];

    protected function casts(): array
    {
        return [
            'fee_percentage' => 'decimal:2',
        ];
    }
}
