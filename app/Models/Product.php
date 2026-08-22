<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'sku',
        'category',
        'unit',
        'purchase_price',
        'sale_price',
        'reorder_level',
        'description',
        'is_active',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'sale_price'     => 'decimal:2',
        'reorder_level'  => 'integer',
        'is_active'      => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Per-warehouse stock rows for this product.
     */
    public function stocks()
    {
        return $this->hasMany(ProductStock::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Total quantity across all warehouses.
     */
    public function totalStock(): float
    {
        return (float) $this->stocks->sum('quantity');
    }

    /**
     * True when total stock has fallen at/below the reorder level.
     */
    public function isLowStock(): bool
    {
        return $this->totalStock() <= (float) $this->reorder_level;
    }
}