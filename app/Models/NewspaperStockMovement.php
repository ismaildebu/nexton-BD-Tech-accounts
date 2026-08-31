<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * NewspaperStockMovement
 * -----------------------
 * Append-only movement log for a publication's stock. The running
 * balance is always derived by summing `quantity` (signed) — never
 * stored — mirroring the existing StockMovement (inventory) model.
 */
class NewspaperStockMovement extends Model
{
    use BelongsToCompany;

    public const TYPE_OPENING      = 'opening';
    public const TYPE_PRINTED      = 'printed';
    public const TYPE_RECEIVED     = 'received';
    public const TYPE_DISTRIBUTION = 'distribution';
    public const TYPE_RETURN       = 'return';
    public const TYPE_DAMAGE       = 'damage';
    public const TYPE_ADJUSTMENT   = 'adjustment';

    public const TYPES = [
        self::TYPE_OPENING,
        self::TYPE_PRINTED,
        self::TYPE_RECEIVED,
        self::TYPE_DISTRIBUTION,
        self::TYPE_RETURN,
        self::TYPE_DAMAGE,
        self::TYPE_ADJUSTMENT,
    ];

    protected $fillable = [
        'company_id',
        'publication_id',
        'movement_date',
        'type',
        'quantity',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'quantity'      => 'integer',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The document that caused this movement (PrintOrder, MediaDistribution,
     * MediaReturn, ...), addressed generically via reference_type/reference_id.
     */
    public function reference(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'reference_type', 'reference_id');
    }

    // ---------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------

    public function scopeForPublication(Builder $query, int $publicationId): Builder
    {
        return $query->where('publication_id', $publicationId);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }
}
