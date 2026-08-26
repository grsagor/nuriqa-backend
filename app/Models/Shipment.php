<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    protected $fillable = [
        'transaction_id',
        'seller_id',
        'carrier',
        'tracking_number',
        'label_url',
        'status',
        'shipping_fee',
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
            'shipping_fee' => 'decimal:2',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
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
