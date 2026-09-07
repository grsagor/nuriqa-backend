<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportCase extends Model
{
    public const TYPE_DAMAGE = 'damage';

    public const TYPE_DISPUTE = 'dispute';

    public const TYPE_QUERY = 'query';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_AWAITING_CUSTOMER = 'awaiting_customer';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'case_number',
        'type',
        'status',
        'user_id',
        'owner_id',
        'transaction_id',
        'product_id',
        'subject',
        'description',
        'evidence',
        'decision',
        'financial_outcome',
        'admin_notes',
        'acknowledged_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'evidence' => 'array',
            'financial_outcome' => 'decimal:2',
            'acknowledged_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportCaseMessage::class);
    }
}
