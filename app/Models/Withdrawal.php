<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'status',
        'payment_method',
        'payment_details',
        'rejection_reason',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_details' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class, 'user_id', 'user_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(SellerPaymentMethod::class, 'payment_method_id');
    }

    public function approve()
    {
        $this->status = 'approved';
        $this->processed_at = now();
        $this->save();
    }

    public function reject($reason = null)
    {
        $this->status = 'rejected';
        $this->rejection_reason = $reason;
        $this->processed_at = now();
        
        // Refund the amount back to available balance
        $wallet = Wallet::getOrCreateForUser($this->user_id);
        $wallet->available_balance += $this->amount;
        $wallet->save();
        
        $this->save();
    }

    public function process()
    {
        $this->status = 'processed';
        $this->processed_at = now();
        $this->save();
    }
}
