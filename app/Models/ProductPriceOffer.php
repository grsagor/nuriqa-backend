<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceOffer extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CONSUMED = 'consumed';

    protected $fillable = [
        'product_id',
        'buyer_id',
        'offered_unit_price',
        'status',
        'approved_at',
        'approved_until',
        'consumed_at',
        'transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'offered_unit_price' => 'decimal:2',
            'approved_at' => 'datetime',
            'approved_until' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
