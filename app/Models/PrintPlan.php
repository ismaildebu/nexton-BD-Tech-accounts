<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrintPlan extends Model
{
    use SoftDeletes;
    use BelongsToCompany;

    public const STATUS_DRAFT     = 'Draft';
    public const STATUS_SUBMITTED = 'Submitted';
    public const STATUS_APPROVED  = 'Approved';
    public const STATUS_REJECTED  = 'Rejected';

    protected $fillable = [
        'company_id',
        'publication_id',
        'plan_date',
        'previous_distribution_quantity',
        'average_distribution_quantity',
        'expected_paid_quantity',
        'expected_free_quantity',
        'expected_total_quantity',
        'buffer_quantity',
        'recommended_quantity',
        'adjusted_quantity',
        'adjustment_reason',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'plan_date'                       => 'date',
        'previous_distribution_quantity'  => 'integer',
        'average_distribution_quantity'   => 'integer',
        'expected_paid_quantity'          => 'integer',
        'expected_free_quantity'          => 'integer',
        'expected_total_quantity'         => 'integer',
        'buffer_quantity'                 => 'integer',
        'recommended_quantity'            => 'integer',
        'adjusted_quantity'               => 'integer',
        'approved_at'                     => 'datetime',
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

    public function printOrders(): HasMany
    {
        return $this->hasMany(PrintOrder::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /**
     * The quantity that should actually be printed: the human override
     * if one was made, otherwise the system recommendation.
     */
    public function getFinalQuantityAttribute(): int
    {
        return $this->adjusted_quantity ?? $this->recommended_quantity;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
