<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * PurchaseOrderReceivingService
 *
 * Confirmed -> Received:
 *
 *   For every Purchase Order item
 *       - lock the product/warehouse stock row
 *       - create a stock "in" movement (cost = PO unit price)
 *       - increase the stock quantity
 *
 * Everything runs in one transaction. If any item fails, no stock
 * changes at all and the Purchase Order stays Confirmed.
 *
 * This service only moves STOCK. Accounting (ledger) is recorded by
 * the Purchase Bill, so the same purchase is never counted twice.
 */
final class PurchaseOrderReceivingService
{
    /**
     * @throws RuntimeException
     */
    public function receive(PurchaseOrder $purchaseOrder): void
    {
        DB::transaction(function () use ($purchaseOrder): void {
            $order = PurchaseOrder::query()
                ->where('company_id', $purchaseOrder->company_id)
                ->lockForUpdate()
                ->findOrFail($purchaseOrder->id);

            $companyId = (int) $order->company_id;
            $reference = $this->reference($order);

            if ($order->status !== 'Confirmed') {
                throw new RuntimeException(
                    "Only a Confirmed purchase order can be received "
                    . "(current status: {$order->status})."
                );
            }

            $userId = Auth::id();

            if ($userId === null) {
                throw new RuntimeException(
                    'Authenticated user is required to receive stock.'
                );
            }

            /*
             * Never add stock twice for the same Purchase Order.
             */
            $alreadyReceived = StockMovement::query()
                ->where('company_id', $companyId)
                ->where('reference_type', 'PurchaseOrder')
                ->where('reference_id', $order->id)
                ->exists();

            if ($alreadyReceived) {
                throw new RuntimeException(
                    "Stock for {$reference} was already received."
                );
            }

            $items = $order->items()->get();

            if ($items->isEmpty()) {
                throw new RuntimeException(
                    "{$reference} has no items."
                );
            }

            $warehouseId = $this->resolveWarehouseId($order);

            foreach ($items as $item) {
                if (empty($item->product_id)) {
                    throw new RuntimeException(
                        "{$reference} item #{$item->id} does not have "
                        . 'a product selected.'
                    );
                }

                $product = Product::query()
                    ->whereKey($item->product_id)
                    ->where('company_id', $companyId)
                    ->first();

                if (! $product) {
                    throw new RuntimeException(
                        "Product #{$item->product_id} does not belong "
                        . "to company #{$companyId}."
                    );
                }

                $quantity = $this->toDecimal($item->quantity);

                if (bccomp($quantity, '0', 4) <= 0) {
                    throw new RuntimeException(
                        "Invalid quantity for product #{$product->id} "
                        . "in {$reference}."
                    );
                }

                $unitCost = $this->toDecimal($item->unit_price);

                if (bccomp($unitCost, '0', 4) < 0) {
                    throw new RuntimeException(
                        "Invalid unit price for product #{$product->id} "
                        . "in {$reference}."
                    );
                }

                $totalCost = bcmul($quantity, $unitCost, 4);

                /*
                 * Lock the exact product/warehouse stock row.
                 * A missing row means zero stock, so it is created first.
                 */
                $stock = ProductStock::query()
                    ->where('product_id', $product->id)
                    ->where('warehouse_id', $warehouseId)
                    ->lockForUpdate()
                    ->first();

                if (! $stock) {
                    ProductStock::create([
                        'company_id' => $companyId,
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouseId,
                        'quantity' => '0.0000',
                    ]);

                    $stock = ProductStock::query()
                        ->where('product_id', $product->id)
                        ->where('warehouse_id', $warehouseId)
                        ->lockForUpdate()
                        ->firstOrFail();
                }

                StockMovement::create([
                    'company_id' => $companyId,
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouseId,

                    'type' => 'in',

                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,

                    'reference' => $reference,
                    'reference_type' => 'PurchaseOrder',
                    'reference_id' => $order->id,

                    'notes' => "Stock received from {$reference}",

                    'movement_date' => Carbon::today(),

                    'created_by' => $userId,
                ]);

                $stock->increment('quantity', $quantity);
            }
        });
    }

    /**
     * Warehouse that receives the goods.
     *
     * 1. The Purchase Order's own warehouse, when it has one.
     * 2. Otherwise the company's only warehouse.
     * 3. Otherwise receiving is refused (never guess).
     *
     * @throws RuntimeException
     */
    private function resolveWarehouseId(PurchaseOrder $order): int
    {
        $companyId = (int) $order->company_id;

        $attributes = $order->getAttributes();

        $orderWarehouseId = array_key_exists('warehouse_id', $attributes)
            ? $attributes['warehouse_id']
            : null;

        if (! empty($orderWarehouseId)) {
            $warehouse = Warehouse::query()
                ->whereKey($orderWarehouseId)
                ->where('company_id', $companyId)
                ->first();

            if (! $warehouse) {
                throw new RuntimeException(
                    "Warehouse #{$orderWarehouseId} does not belong "
                    . "to company #{$companyId}."
                );
            }

            return (int) $warehouse->id;
        }

        $warehouseIds = Warehouse::query()
            ->where('company_id', $companyId)
            ->pluck('id');

        if ($warehouseIds->isEmpty()) {
            throw new RuntimeException(
                'No warehouse is configured for this company.'
            );
        }

        if ($warehouseIds->count() > 1) {
            throw new RuntimeException(
                'This purchase order has no warehouse and the company has '
                . 'more than one. Set the warehouse before receiving.'
            );
        }

        return (int) $warehouseIds->first();
    }

    private function reference(PurchaseOrder $order): string
    {
        $attributes = $order->getAttributes();

        if (! empty($attributes['po_number'])) {
            return (string) $attributes['po_number'];
        }

        return 'PO-' . $order->id;
    }

    /**
     * Normalize a database value into a plain decimal string for bcmath.
     */
    private function toDecimal(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '0.0000';
        }

        $string = trim((string) $value);

        if (preg_match('/^-?\d+(\.\d+)?$/', $string) === 1) {
            return bcadd($string, '0', 4);
        }

        return number_format((float) $value, 4, '.', '');
    }
}