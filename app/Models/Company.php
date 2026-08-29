<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'owner_id',
        'company_name',
        'owner_name',
        'email',
        'phone',
        'logo',
        'address',
        'city',
        'country',
        'currency',
        'currency_symbol',
        'financial_year',
        'status',
        'business_type',
    ];

    public const INVENTORY_ENABLED_TYPES = ['Trading', 'Manufacturing', 'Hospital'];
    public const SALES_ORDER_ENABLED_TYPES = ['Trading', 'Manufacturing'];
    public const MEDIA_ENABLED_TYPES = ['Media'];

    public function hasModule(string $module): bool
    {
        return match ($module) {
            'inventory'    => in_array($this->business_type, self::INVENTORY_ENABLED_TYPES, true),
            'sales-orders' => in_array($this->business_type, self::SALES_ORDER_ENABLED_TYPES, true),
            'media'        => in_array($this->business_type, self::MEDIA_ENABLED_TYPES, true),
            default        => true,
        };
    }

    // Billing owner — governs plan limits
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function financialYears(): HasMany
    {
        return $this->hasMany(FinancialYear::class);
    }

    public function voucherTypes(): HasMany
    {
        return $this->hasMany(VoucherType::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class);
    }

    public function mediaParties(): HasMany
    {
        return $this->hasMany(MediaParty::class);
    }
}