<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'available_balance',
        'pending_balance',
        'total_earnings',
    ];

    protected function casts(): array
    {
        return [
            'available_balance' => 'decimal:2',
            'pending_balance' => 'decimal:2',
            'total_earnings' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class, 'user_id', 'user_id');
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(SellerPaymentMethod::class);
    }

    public static function getOrCreateForUser($userId)
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'available_balance' => 0,
                'pending_balance' => 0,
                'total_earnings' => 0,
            ]
        );
    }

    public function addEarnings($amount)
    {
        $this->total_earnings += $amount;
        $this->pending_balance += $amount;
        $this->save();
    }

    public function makeAvailable($amount)
    {
        if ($amount > $this->pending_balance) {
            throw new \InvalidArgumentException('Insufficient pending balance');
        }
        
        $this->pending_balance -= $amount;
        $this->available_balance += $amount;
        $this->save();
    }

    public function withdraw($amount)
    {
        if ($amount > $this->available_balance) {
            throw new \InvalidArgumentException('Insufficient available balance');
        }
        
        $this->available_balance -= $amount;
        $this->save();
    }
}
