<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'image',
    ];

    protected $appends = [
        'image_url',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getImageUrlAttribute(): string
    {
        if (! $this->image) {
            return asset('assets/img/utils/no-image.png');
        }

        $imagePath = public_path($this->image);

        return file_exists($imagePath) ? asset($this->image) : asset('assets/img/utils/no-image.png');
    }
}
