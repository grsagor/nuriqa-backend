<?php

namespace App\Models;

use App\Services\PlatformFeeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    public const APPROVAL_PENDING = 'pending';

    public const APPROVAL_APPROVED = 'approved';

    public const APPROVAL_REJECTED = 'rejected';

    public const APPROVAL_RETURNED = 'returned';

    public const CONTRIBUTION_FIXED = 'fixed';

    public const CONTRIBUTION_FLEXIBLE = 'flexible';

    public const DELIVERY_PAYER_BUYER = 'buyer';

    public const DELIVERY_PAYER_DONOR = 'donor';

    protected $fillable = [
        'owner_id',
        'title',
        'type',
        'description',
        'is_washed',
        'location',
        'upload_date',
        'brand',
        'size_id',
        'category_id',
        'condition',
        'material',
        'color',
        'price',
        'guide_value',
        'thumbnail',
        'is_featured',
        'is_free',
        'contribution_mode',
        'delivery_payer',
        'discount_enabled',
        'discount_type',
        'discount',
        'platform_donation',
        'donation_percentage',
        'cause_allocation_type',
        'cause_allocation_value',
        'cause_id',
        'active_listing',
        'approval_status',
        'rejection_reason',
        'moderation_message',
        'stock',
    ];

    protected $appends = [
        'thumbnail_url',
        'platform_fee_percentage',
        'platform_fee_amount',
        'unit_price_including_platform_fee',
    ];

    protected function casts(): array
    {
        return [
            'is_washed' => 'boolean',
            'is_featured' => 'boolean',
            'is_free' => 'boolean',
            'discount_enabled' => 'boolean',
            'platform_donation' => 'boolean',
            'active_listing' => 'boolean',
            'upload_date' => 'date',
            'price' => 'decimal:2',
            'guide_value' => 'decimal:2',
            'cause_allocation_value' => 'decimal:2',
            'discount' => 'decimal:2',
            'donation_percentage' => 'integer',
            'stock' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function cause(): BelongsTo
    {
        return $this->belongsTo(Cause::class);
    }

    public function approvalHistories(): HasMany
    {
        return $this->hasMany(ProductApprovalHistory::class)->latest();
    }

    public function allowsFlexibleContribution(): bool
    {
        return ($this->contribution_mode ?? self::CONTRIBUTION_FIXED) === self::CONTRIBUTION_FLEXIBLE
            || $this->is_free
            || (float) ($this->price ?? 0) <= 0;
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function isSellerListing(): bool
    {
        return $this->type === 'seller' || $this->type === null || $this->type === '';
    }

    public function scopePubliclyVisible(Builder $query): void
    {
        $query->where(function (Builder $q) {
            $q->whereIn('type', ['merchandise', 'hajra'])
                ->orWhere(function (Builder $sub) {
                    $sub->where(function (Builder $typeQ) {
                        $typeQ->where('type', 'seller')
                            ->orWhereNull('type')
                            ->orWhere('type', '');
                    })->where('approval_status', self::APPROVAL_APPROVED);
                });
        });
    }

    public static $materials = [
        'cotton' => 'Cotton',
        'polyester' => 'Polyester',
        'wool' => 'Wool',
        'silk' => 'Silk',
        'linen' => 'Linen',
        'denim' => 'Denim',
        'leather' => 'Leather',
        'synthetic' => 'Synthetic',
        'other' => 'Other',
    ];

    public function getThumbnailUrlAttribute()
    {
        if (! $this->thumbnail) {
            return asset('assets/img/utils/no-image.png');
        }

        $thumbnailPath = public_path($this->thumbnail);

        return file_exists($thumbnailPath) ? asset($this->thumbnail) : asset('assets/img/utils/no-image.png');
    }

    public function getPlatformFeePercentageAttribute(): float
    {
        return PlatformFeeService::feePercentage();
    }

    public function getPlatformFeeAmountAttribute(): float
    {
        $unit = (float) ($this->price ?? 0);

        return PlatformFeeService::platformFeeAmountForUnitPrice($unit, $this);
    }

    public function getUnitPriceIncludingPlatformFeeAttribute(): float
    {
        $unit = (float) ($this->price ?? 0);

        return PlatformFeeService::unitPriceIncludingPlatformFee($unit, $this);
    }
}
