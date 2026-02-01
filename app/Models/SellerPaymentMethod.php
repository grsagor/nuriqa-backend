<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class SellerPaymentMethod extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'provider',
        'account_name',
        'account_details',
        'last4',
        'is_default',
        'is_verified',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'account_details' => 'array',
            'is_default' => 'boolean',
            'is_verified' => 'boolean',
        ];
    }

    protected $hidden = [
        'account_details'
    ];

    protected $appends = ['masked_account_details'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getMaskedAccountDetailsAttribute()
    {
        if (!$this->account_details) {
            return null;
        }

        $details = $this->account_details;
        
        switch ($this->type) {
            case 'bank_account':
                return [
                    'bank_name' => $details['bank_name'] ?? null,
                    'account_number' => '****' . ($details['account_number'] ?? ''),
                    'sort_code' => $details['sort_code'] ?? null,
                ];
            case 'stripe_account':
                return [
                    'email' => $details['email'] ?? null,
                    'country' => $details['country'] ?? null,
                ];
            case 'paypal':
                return [
                    'email' => $details['email'] ?? null,
                ];
            default:
                return null;
        }
    }

    public function setAccountDetailsAttribute($value)
    {
        $this->attributes['account_details'] = Crypt::encrypt(json_encode($value));
    }

    public function getAccountDetailsAttribute($value)
    {
        if (!$value) {
            return null;
        }
        
        return json_decode(Crypt::decrypt($value), true);
    }

    public static function setDefault($userId, $methodId)
    {
        // Remove default from all other methods
        static::where('user_id', $userId)
            ->where('id', '!=', $methodId)
            ->update(['is_default' => false]);
            
        // Set new default
        static::where('user_id', $userId)
            ->where('id', $methodId)
            ->update(['is_default' => true]);
    }

    public static function getDefault($userId)
    {
        return static::where('user_id', $userId)
            ->where('is_default', true)
            ->first();
    }
}
