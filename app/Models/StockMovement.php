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
    public static function adjustStock(
        int $productId,
        int $warehouseId,
        float|string $quantity,
        string $type
    ): void {
        $product = Product::query()->findOrFail($productId);
        $warehouse = Warehouse::query()->findOrFail($warehouseId);

        if ((int) $product->company_id !== (int) $warehouse->company_id) {
            throw new \RuntimeException(
                'Product and warehouse must belong to the same company.'
            );
        }

        $stock = ProductStock::query()->firstOrCreate(
            [
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
            ],
            [
                'quantity' => 0,
            ]
        );

        if ($type === 'in') {
            $stock->increment('quantity', $quantity);

            return;
        }

        if ($type === 'out') {
            $stock->decrement('quantity', $quantity);

            return;
        }

        throw new \InvalidArgumentException(
            "Unsupported stock movement type: {$type}"
        );
    }
    
}

