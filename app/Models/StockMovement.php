<?php

namespace App\Models;
use App\Models\Concerns\BelongsToCompany;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use BelongsToCompany;
    protected $fillable = [
        'company_id', 'product_id', 'warehouse_id',
        'type', 'quantity', 'unit_cost', 'total_cost',
        'reference', 'reference_type', 'reference_id',
        'notes', 'movement_date', 'created_by',
    ];

    protected $casts = [
        'movement_date' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Helper: adjust stock
    public static function adjustStock($product_id, $warehouse_id, $quantity, $type)
    {
        $stock = ProductStock::firstOrCreate(
            ['product_id' => $product_id, 'warehouse_id' => $warehouse_id],
            ['quantity' => 0]
        );

        if ($type === 'in') {
            $stock->increment('quantity', $quantity);
        } elseif ($type === 'out') {
            $stock->decrement('quantity', $quantity);
        }
    }
}

