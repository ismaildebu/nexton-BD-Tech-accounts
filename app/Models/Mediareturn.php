<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaReturn extends Model
{
    use SoftDeletes;
    use BelongsToCompany;

    public const STATUS_DRAFT     = 'Draft';
    public const STATUS_CONFIRMED = 'Confirmed';
    public const STATUS_CANCELLED = 'Cancelled';

    protected $fillable = [
        'company_id',
        'publication_id',
        'media_distribution_id',
        'return_date',
        'status',
        'total_paid_return_quantity',
        'total_free_return_quantity',
        'total_return_quantity',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'return_date'                 => 'date',
        'total_paid_return_quantity'  => 'integer',
        'total_free_return_quantity'  => 'integer',
        'total_return_quantity'       => 'integer',
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

    public function distribution(): BelongsTo
    {
        return $this->belongsTo(MediaDistribution::class, 'media_distribution_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MediaReturnItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
