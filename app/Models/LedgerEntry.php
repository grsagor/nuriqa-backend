<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LedgerEntry extends Model
{
    public const TYPE_CONTRIBUTION = 'contribution';

    public const TYPE_ITEM_PRICE = 'item_price';

    public const TYPE_ADMIN_FEE = 'admin_fee';

    public const TYPE_DELIVERY = 'delivery';

    public const TYPE_CAUSE_ALLOCATION = 'cause_allocation';

    public const TYPE_SELLER_BALANCE = 'seller_balance';

    public const TYPE_PROCESSOR_FEE = 'processor_fee';

    public const TYPE_REFUND = 'refund';

    public const TYPE_REVERSAL = 'reversal';

    protected $fillable = [
        'transaction_id',
        'user_id',
        'entry_type',
        'direction',
        'amount',
        'currency',
        'reference_type',
        'reference_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'reference_type', 'reference_id');
    }
}
