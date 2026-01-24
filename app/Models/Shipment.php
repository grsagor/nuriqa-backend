<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    protected $fillable = [
        'transaction_id',
        'carrier',
        'tracking_number',
        'label_url',
        'status',
        'address_to',
        'address_from',
        'weight_g',
        'dimensions_cm',
    ];

    protected function casts(): array
    {
        return [
            'address_to' => 'array',
            'address_from' => 'array',
            'dimensions_cm' => 'array',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function scopeByCarrier($query, string $carrier)
    {
        return $query->where('carrier', $carrier);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
