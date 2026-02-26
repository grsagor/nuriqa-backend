<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerNotification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'entity_type',
        'entity_id',
        'title',
        'description',
        'read',
    ];

    protected function casts(): array
    {
        return [
            'entity_id' => 'integer',
            'read' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
