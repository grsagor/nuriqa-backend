<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $fillable = [
        'fee_percentage',
        'admin_fee_amount',
    ];

    protected function casts(): array
    {
        return [
            'fee_percentage' => 'decimal:2',
            'admin_fee_amount' => 'decimal:2',
        ];
    }
}
