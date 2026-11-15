<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockMovement extends Model
{
    use BelongsToCompany;

    
    protected static function booted(): void
    {
        static::creating(function (StockMovement $movement): void {
            $companyId = (int) $movement->company_id;

            if ($companyId <= 0) {
                throw new RuntimeException(
                    'Company context is required for stock movement.'
                );
            }

            if ($movement->quantity <= 0) {
                throw new RuntimeException(
                    'Stock movement quantity must be greater than zero.'
                );
            }

            if ($movement->unit_cost < 0) {
                throw new RuntimeException(
                    'Stock movement unit cost cannot be negative.'
                );
            }

            $expectedTotalCost = round(
                (float) $movement->quantity * (float) $movement->unit_cost,
                2
            );

            if (round((float) $movement->total_cost, 2) !== $expectedTotalCost) {
                throw new RuntimeException(
                    'Stock movement total cost does not match quantity multiplied by unit cost.'
                );
            }

            if (! in_array($movement->type, ['in', 'out', 'transfer', 'adjustment'], true)) {
                throw new RuntimeException(
                    "Invalid stock movement type: {$movement->type}."
                );
            }

            $productBelongsToCompany = Product::withoutCompanyScope()
                ->whereKey($movement->product_id)
                ->where('company_id', $companyId)
                ->exists();

            $warehouseBelongsToCompany = Warehouse::withoutCompanyScope()
                ->whereKey($movement->warehouse_id)
                ->where('company_id', $companyId)
                ->exists();

            if (! $productBelongsToCompany || ! $warehouseBelongsToCompany) {
                throw new RuntimeException(
                    'Product and warehouse must belong to the stock movement company.'
                );
            }
        });

        static::updating(function (): void {
    throw new RuntimeException(
        'Stock movements cannot be modified after creation.'
    );
});

    }

    protected $fillable = [
        'company_id',
        'product_id',
        'warehouse_id',
        'type',
        'quantity',
        'unit_cost',
        'total_cost',
        'reference',
        'reference_type',
        'reference_id',
        'notes',
        'movement_date',
        'created_by',
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

    /**
     * Atomically adjust product stock for the current company.
     *
     * The caller should execute this inside the same database
     * transaction as the corresponding stock movement.
     */
    public static function adjustStock(
        int $product_id,
        int $warehouse_id,
        float|int $quantity,
        string $type
    ): ProductStock {
        $companyId = session('company_id');

        if (empty($companyId)) {
            throw new RuntimeException(
                'Company context is required to adjust stock.'
            );
        }

        if ($quantity <= 0) {
            throw new RuntimeException(
                'Stock adjustment quantity must be greater than zero.'
            );
        }

        if (! in_array($type, ['in', 'out'], true)) {
            throw new RuntimeException(
                "Invalid stock adjustment type: {$type}."
            );
        }

        $productBelongsToCompany = Product::withoutCompanyScope()
            ->whereKey($product_id)
            ->where('company_id', $companyId)
            ->exists();

        $warehouseBelongsToCompany = Warehouse::withoutCompanyScope()
            ->whereKey($warehouse_id)
            ->where('company_id', $companyId)
            ->exists();

        if (! $productBelongsToCompany || ! $warehouseBelongsToCompany) {
            throw new RuntimeException(
                'Product and warehouse must belong to the current company.'
            );
        }




        /*
         * Ensure the stock row exists for this exact tenant/product/
         * warehouse combination.
         *
         * insertOrIgnore prevents a duplicate-row race when the stock
         * row does not yet exist and concurrent requests create it.
         */
        DB::table('product_stocks')->insertOrIgnore([
            'company_id' => $companyId,
            'product_id' => $product_id,
            'warehouse_id' => $warehouse_id,
            'quantity' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /*
         * Lock the exact tenant-scoped stock row before checking or
         * changing its quantity.
         */
        $stock = ProductStock::query()
            ->where('company_id', $companyId)
            ->where('product_id', $product_id)
            ->where('warehouse_id', $warehouse_id)
            ->lockForUpdate()
            ->first();

        if (! $stock) {
            throw new RuntimeException(
                'Unable to locate the product stock record.'
            );
        }

        if ($type === 'out') {
            $currentQuantity = (float) $stock->quantity;

            if ($currentQuantity < $quantity) {
                throw new RuntimeException(
                    "Insufficient stock. Available: {$currentQuantity}, requested: {$quantity}."
                );
            }

            $stock->quantity = $currentQuantity - $quantity;
        } else {
            $stock->quantity = (float) $stock->quantity + $quantity;
        }

        $stock->save();

        return $stock;
    }
}