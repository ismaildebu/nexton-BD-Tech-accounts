<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Publication extends Model
{
    use SoftDeletes;
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'publication_type',
        'selling_price',
        'sales_account_id',
        'sales_return_account_id',
        'default_free_percentage',
        'is_active',
    ];

    protected $casts = [
        'selling_price'            => 'decimal:2',
        'default_free_percentage'  => 'decimal:2',
        'is_active'                => 'boolean',
    ];

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    public function salesAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'sales_account_id');
    }

    public function salesReturnAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'sales_return_account_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function printPlans(): HasMany
    {
        return $this->hasMany(PrintPlan::class);
    }

    public function printOrders(): HasMany
    {
        return $this->hasMany(PrintOrder::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(NewspaperStockMovement::class);
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(MediaDistribution::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(MediaReturn::class);
    }

    // ---------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
