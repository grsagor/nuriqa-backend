<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SponsorRequest extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'request_reason',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'apartment',
        'city',
        'postal_code',
        'additional_info',
        'keep_updated',
        'status',
    ];

    protected $casts = [
        'keep_updated' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
