<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TraderDocument extends Model
{
    public const IDENTITY = 'identity';

    public const BANK_PROOF = 'bank_proof';

    public const PRODUCT_EVIDENCE = 'product_evidence';

    public const BUSINESS_PROOF = 'business_proof';

    public const TYPES = [self::IDENTITY, self::BANK_PROOF, self::PRODUCT_EVIDENCE, self::BUSINESS_PROOF];

    /** Without at least one of each of these a verification cannot be submitted. */
    public const REQUIRED_TYPES = [self::IDENTITY, self::BANK_PROOF, self::PRODUCT_EVIDENCE];

    protected $fillable = ['trader_verification_id', 'type', 'path', 'original_name', 'mime_type', 'size'];

    protected $hidden = ['path'];

    public function verification(): BelongsTo
    {
        return $this->belongsTo(TraderVerification::class, 'trader_verification_id');
    }
}
