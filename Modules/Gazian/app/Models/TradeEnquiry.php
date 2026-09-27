<?php

namespace Modules\Gazian\Models;

use Illuminate\Database\Eloquent\Model;

class TradeEnquiry extends Model
{
    protected $table = 'gazian_trade_enquiries';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'interest',
        'business_type',
        'explore',
        'country',
        'city',
        'is_read',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'explore' => 'array',
            'is_read' => 'boolean',
        ];
    }
}
