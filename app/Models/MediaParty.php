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
 * One shared table for Agent and Hawker, distinguished by `type`.
 *
 * IMPORTANT BUSINESS RULE: Agent and Hawker are completely independent.
 * There is NO Agent -> Hawker relationship anywhere on this model, and
 * none should ever be added.
 */
class MediaParty extends Model
{
    use SoftDeletes;
    use BelongsToCompany;

    public const TYPE_AGENT  = 'agent';
    public const TYPE_HAWKER = 'hawker';

    public const TYPES = [
        self::TYPE_AGENT,
        self::TYPE_HAWKER,
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
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'free_percentage' => 'decimal:2',
        'is_active'       => 'boolean',
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
}
