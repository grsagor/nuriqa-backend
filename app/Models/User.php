<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\ImageService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'firebase_uid',
        'password',
        'phone',
        'image',
        'address',
        'apartment',
        'city',
        'postal_code',
        'rating',
        'reviews',
        'signup_date',
        'lang_id',
        'role_id',
        'notification_settings',
        'email_verified_at',
        'otp',
        'otp_expires_at',
    ];

    protected $appends = ['image_url'];

    /**
     * Columns safe to expose on public product/seller payloads.
     *
     * @var list<string>
     */
    public const PUBLIC_PROFILE_COLUMNS = [
        'id',
        'name',
        'image',
        'rating',
        'reviews',
        'signup_date',
        'created_at',
    ];

    /**
     * Private attributes that must never appear on accidental JSON serialization.
     * Authenticated profile endpoints call makePrivateAttributesVisible().
     *
     * @var list<string>
     */
    public const PRIVATE_SERIALIZATION_ATTRIBUTES = [
        'email',
        'phone',
        'address',
        'apartment',
        'city',
        'postal_code',
        'notification_settings',
        'otp',
        'otp_expires_at',
        'firebase_uid',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email',
        'phone',
        'address',
        'apartment',
        'city',
        'postal_code',
        'notification_settings',
        'otp',
        'otp_expires_at',
        'firebase_uid',
    ];

    /**
     * Reveal private profile fields for the authenticated owner only.
     */
    public function makePrivateAttributesVisible(): static
    {
        return $this->makeVisible(self::PRIVATE_SERIALIZATION_ATTRIBUTES);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'password' => 'hashed',
            'notification_settings' => 'array',
        ];
    }

    /**
     * Get the role associated with the user.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the language associated with the user.
     */
    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * Get the wallet associated with the user.
     */
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Get the seller notifications for this user.
     */
    public function sellerNotifications()
    {
        return $this->hasMany(SellerNotification::class);
    }

    /**
     * Get the image URL attribute.
     */
    public function getImageUrlAttribute()
    {
        return ImageService::getUrl($this->image, asset('assets/img/utils/no-image.png'));
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
