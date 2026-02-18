<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
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
        'thumbnail',
        'is_featured',
        'is_free',
        'discount_enabled',
        'discount_type',
        'discount',
        'platform_donation',
        'donation_percentage',
        'active_listing',
    ];

    protected $appends = [
        'thumbnail_url',
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
            'discount' => 'decimal:2',
            'donation_percentage' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
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
}
