<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * MediaParty
 * ----------
 * One shared table for Agent, Hawker, and Journalist, distinguished by `type`.
 *
 * IMPORTANT BUSINESS RULE: Agent, Hawker, and Journalist are completely independent.
 * There is NO relationship between types anywhere on this model.
 */
class MediaParty extends Model
{
    use SoftDeletes;
    use BelongsToCompany;

    public const TYPE_AGENT      = 'agent';
    public const TYPE_HAWKER     = 'hawker';
    public const TYPE_JOURNALIST = 'journalist';   // ← নতুন

    public const TYPES = [
        self::TYPE_AGENT,
        self::TYPE_HAWKER,
        self::TYPE_JOURNALIST,                     // ← নতুন
    ];

    public const BALANCE_RECEIVABLE = 'Receivable';
    public const BALANCE_PAYABLE    = 'Payable';
    public const BALANCE_ADVANCE    = 'Advance';

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'code',
        'phone',
        'alternate_phone',
        'address',
        'area',
        'opening_balance',
        'balance_type',
        'account_id',
        'free_percentage',
        'is_active',
        // ── journalist-specific (nullable for agent/hawker) ──
        'beat',
        'email',
        'district',
        'media_outlet',
        'commission_percent',
    ];

    protected $casts = [
        'opening_balance'    => 'decimal:2',
        'free_percentage'    => 'decimal:2',
        'commission_percent' => 'decimal:2',       // ← নতুন
        'is_active'          => 'boolean',
    ];

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function distributionItems(): HasMany
    {
        return $this->hasMany(MediaDistributionItem::class);
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany(MediaReturnItem::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(MediaCollection::class);
    }

    // ---------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAgents(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_AGENT);
    }

    public function scopeHawkers(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_HAWKER);
    }

    public function scopeJournalists(Builder $query): Builder  // ← নতুন
    {
        return $query->where('type', self::TYPE_JOURNALIST);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    public function isAgent(): bool
    {
        return $this->type === self::TYPE_AGENT;
    }

    public function isHawker(): bool
    {
        return $this->type === self::TYPE_HAWKER;
    }

    public function isJournalist(): bool  // ← নতুন
    {
        return $this->type === self::TYPE_JOURNALIST;
    }
}
