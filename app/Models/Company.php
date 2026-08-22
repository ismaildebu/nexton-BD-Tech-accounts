<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
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


    /**
     * business_type values that unlock Inventory, Warehouse and
     * Stock Transfer modules.
     */
    public const INVENTORY_ENABLED_TYPES = ['Trading', 'Manufacturing', 'Hospital'];

    /**
     * business_type values that unlock the Sales Orders module.
     */
    public const SALES_ORDER_ENABLED_TYPES = ['Trading', 'Manufacturing'];

    /**
     * business_type values that unlock the Media Business module
     * (Publication, Agent/Hawker, Print Planning, Distribution, ...).
     */
    public const MEDIA_ENABLED_TYPES = ['Media'];

    /**
     * Whether this company's business_type has access to the given module.
     *
     * Supported module keys: 'inventory', 'sales-orders', 'media'.
     */
    public function hasModule(string $module): bool
    {
        return match ($module) {
            'inventory' => in_array($this->business_type, self::INVENTORY_ENABLED_TYPES, true),
            'sales-orders' => in_array($this->business_type, self::SALES_ORDER_ENABLED_TYPES, true),
            'media' => in_array($this->business_type, self::MEDIA_ENABLED_TYPES, true),
            default => true,
        };
    }

    public function ledgerEntries()
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class);
    }

    public function mediaParties(): HasMany
    {
        return $this->hasMany(MediaParty::class);
    }


    /*
public function voucherTypes(): HasMany
{
    return $this->hasMany(VoucherType::class, 'company_id');
}

public function transactions(): HasMany
{
    return $this->hasMany(Transaction::class, 'company_id');
}
*/
}