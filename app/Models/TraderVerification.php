<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TraderVerification extends Model
{
    public const DRAFT = 'draft';

    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    /** Fields a trader fills in themselves (everything else is set by the system or an admin). */
    public const FILLABLE_BY_TRADER = [
        'legal_name', 'trading_name', 'business_type', 'companies_house_number',
        'registered_address_line1', 'registered_address_line2', 'registered_city', 'registered_postcode', 'registered_country',
        'trading_address_same_as_registered', 'trading_address_line1', 'trading_address_line2', 'trading_city', 'trading_postcode', 'trading_country',
        'returns_address_same_as_trading', 'returns_address_line1', 'returns_address_line2', 'returns_city', 'returns_postcode', 'returns_country',
        'owner_full_name', 'owner_role', 'contact_name', 'contact_email', 'contact_phone',
        'bank_account_holder', 'bank_sort_code', 'bank_account_number',
        'vat_registered', 'vat_number', 'website_url', 'social_links', 'product_category_ids',
        'confirm_products_genuine', 'confirm_legal_compliance', 'accepted_seller_terms',
    ];

    protected $guarded = ['id', 'user_id', 'status', 'submitted_at', 'reviewed_at', 'reviewed_by', 'rejection_reason', 'internal_notes'];

    protected $hidden = ['bank_sort_code', 'bank_account_number', 'internal_notes'];

    protected function casts(): array
    {
        return [
            'bank_sort_code' => 'encrypted',
            'bank_account_number' => 'encrypted',
            'social_links' => 'array',
            'product_category_ids' => 'array',
            'trading_address_same_as_registered' => 'boolean',
            'returns_address_same_as_trading' => 'boolean',
            'vat_registered' => 'boolean',
            'confirm_products_genuine' => 'boolean',
            'confirm_legal_compliance' => 'boolean',
            'accepted_seller_terms' => 'boolean',
            'terms_accepted_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TraderDocument::class);
    }

    /** A trader can edit until they submit, and again after a rejection. */
    public function isEditable(): bool
    {
        return in_array($this->status, [self::DRAFT, self::REJECTED], true);
    }

    public function isApproved(): bool
    {
        return $this->status === self::APPROVED;
    }
}
