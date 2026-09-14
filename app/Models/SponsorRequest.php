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
        'moderation_message',
        'rejection_reason',
        'moderated_at',
        'moderated_by',
    ];

    protected function casts(): array
    {
        return [
            'keep_updated' => 'boolean',
            'moderated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
