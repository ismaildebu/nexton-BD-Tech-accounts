<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * MediaDistribution
 * ------------------
 * Header for one publication's distribution run on a given day.
 * Designed to hold 100+ MediaDistributionItem lines (one per Agent/Hawker)
 * without a flat per-party table.
 */
class MediaDistribution extends Model
{
    use SoftDeletes;
    use BelongsToCompany;

    public const STATUS_DRAFT     = 'Draft';
    public const STATUS_CONFIRMED = 'Confirmed';
    public const STATUS_CANCELLED = 'Cancelled';

    protected $fillable = [
        'company_id',
        'publication_id',
        'distribution_date',
        'status',
        'total_paid_quantity',
        'total_free_quantity',
        'total_quantity',
        'total_amount',
        'notes',
        'created_by',
        'transaction_id',
    ];

    protected $casts = [
        'distribution_date'   => 'date',
        'total_paid_quantity' => 'integer',
        'total_free_quantity' => 'integer',
        'total_quantity'      => 'integer',
        'total_amount'        => 'decimal:2',
    ];

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MediaDistributionItem::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(MediaReturn::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }
}