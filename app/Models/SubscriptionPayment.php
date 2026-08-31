<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single payment record tied to a Subscription.
 *
 * Payment rows must never be deleted just because the parent subscription
 * is later cancelled or replaced - they are the durable billing history.
 * subscription_payments.subscription_id uses restrictOnDelete() at the DB
 * level to enforce this.
 */
class SubscriptionPayment extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'subscription_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'transaction_reference',
        'paid_at',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}