<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\VoucherType;
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
 *   Stock:
 *     - lock/create product/warehouse stock row
 *     - create stock "in" movement
 *     - increase stock quantity
 *
 *   Accounting:
 *     - Debit  Product Inventory Account
 *     - Credit Purchase Clearing
 *
 * Everything runs in one transaction. If any stock or accounting
 * operation fails, the complete receive operation is rolled back.
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

            /*
             * The accounting transaction must not already exist.
             *
             * If it exists while stock movement does not, the operation
             * is inconsistent and must fail rather than silently creating
             * another accounting entry.
             */
            $existingTransaction = Transaction::query()
                ->where('company_id', $companyId)
                ->where('reference_type', 'PurchaseOrder')
                ->where('reference_id', $order->id)
                ->first();

            if ($existingTransaction !== null) {
                throw new RuntimeException(
                    "{$reference} already has an accounting transaction "
                    . "but has not been received in stock."
                );
            }

            $items = $order->items()->get();

            if ($items->isEmpty()) {
                throw new RuntimeException(
                    "{$reference} has no items."
                );
            }

            /*
             * Current automatic PO receiving accounting supports the
             * inventory value only. Tax and discount require dedicated
             * accounting treatment and therefore must be zero.
             */
            $tax = $this->toDecimal($order->tax);
            $discount = $this->toDecimal($order->discount);

            if (bccomp($tax, '0', 4) !== 0) {
                throw new RuntimeException(
                    "{$reference} tax cannot be posted automatically "
                    . 'until a tax account is configured.'
                );
            }

            if (bccomp($discount, '0', 4) !== 0) {
                throw new RuntimeException(
                    "{$reference} discount cannot be posted automatically "
                    . 'until a discount account is configured.'
                );
            }

            $warehouseId = $this->resolveWarehouseId($order);

            /*
             * Resolve the Purchase Clearing account.
             *
             * This is a company-scoped Liability account and is credited
             * when goods are received. The later Purchase Bill debits this
             * clearing account and credits Vendor Accounts Payable.
             */
            $purchaseClearing = Account::query()
                ->where('company_id', $companyId)
                ->where('account_name', 'Purchase Clearing')
                ->where('account_type', Account::TYPE_LIABILITY)
                ->where('is_active', true)
                ->first();

            if ($purchaseClearing === null) {
                throw new RuntimeException(
                    "Purchase Clearing account is not configured "
                    . "for company #{$companyId}."
                );
            }

            /*
             * Financial year must be active and open for automatic posting.
             */
            $financialYear = FinancialYear::query()
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->where('is_closed', false)
                ->whereDate('start_date', '<=', $order->order_date)
                ->whereDate('end_date', '>=', $order->order_date)
                ->first();

            if ($financialYear === null) {
                throw new RuntimeException(
                    "Open financial year not found for {$reference}."
                );
            }

            /*
             * Existing Purchase Voucher type.
             */
            $voucherType = VoucherType::query()
                ->where('company_id', $companyId)
                ->where('code', 'PUV')
                ->where('is_active', true)
                ->where('status', 1)
                ->first();

            if ($voucherType === null) {
                throw new RuntimeException(
                    "Purchase Voucher type (PUV) is not configured "
                    . "for company #{$companyId}."
                );
            }

            /*
             * Accounting debit lines are aggregated by inventory account.
             *
             * Example:
             *   Product A -> Inventory 1004 -> 500
             *   Product B -> Inventory 1004 -> 300
             *
             * becomes:
             *   Dr Inventory 1004 -> 800
             */
            $inventoryDebits = [];
            $itemSubtotal = '0.0000';

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
                    ->where('is_active', true)
                    ->first();

                if (! $product) {
                    throw new RuntimeException(
                        "Product #{$item->product_id} does not belong "
                        . "to company #{$companyId} or is inactive."
                    );
                }

                if (! $product->inventory_account_id) {
                    throw new RuntimeException(
                        "Product #{$product->id} ({$product->name}) "
                        . 'does not have an inventory account configured.'
                    );
                }

                /*
                 * Inventory account must be a company-scoped active Asset.
                 */
                $inventoryAccount = Account::query()
                    ->where('company_id', $companyId)
                    ->whereKey($product->inventory_account_id)
                    ->where('account_type', Account::TYPE_ASSET)
                    ->where('is_active', true)
                    ->first();

                if (! $inventoryAccount) {
                    throw new RuntimeException(
                        "Inventory account #{$product->inventory_account_id} "
                        . "for product #{$product->id} is invalid "
                        . "for company #{$companyId}."
                    );
                }

                /*
                 * Inventory account must not be Cash, Bank or Customer.
                 */
                if (in_array(
                    $inventoryAccount->nature,
                    [
                        Account::NATURE_CASH,
                        Account::NATURE_BANK,
                        Account::NATURE_CUSTOMER,
                    ],
                    true
                )) {
                    throw new RuntimeException(
                        "Account #{$inventoryAccount->id} "
                        . "({$inventoryAccount->account_name}) cannot be "
                        . 'used as a product inventory account.'
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

                if (bccomp($totalCost, '0', 4) <= 0) {
                    throw new RuntimeException(
                        "Invalid total cost for product #{$product->id} "
                        . "in {$reference}."
                    );
                }

                $itemSubtotal = bcadd(
                    $itemSubtotal,
                    $totalCost,
                    4
                );

                $accountId = (int) $inventoryAccount->id;

                if (! isset($inventoryDebits[$accountId])) {
                    $inventoryDebits[$accountId] = [
                        'account' => $inventoryAccount,
                        'amount' => '0.0000',
                    ];
                }

                $inventoryDebits[$accountId]['amount'] = bcadd(
                    $inventoryDebits[$accountId]['amount'],
                    $totalCost,
                    4
                );

                /*
                 * Lock the exact product/warehouse stock row.
                 * A missing row means zero stock, so it is created first.
                 */
                $stock = ProductStock::query()
                    ->where('company_id', $companyId)
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
                        ->where('company_id', $companyId)
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

            /*
             * The PO subtotal must equal the actual received inventory
             * value. This prevents accounting from silently posting an
             * amount different from the stock value.
             */
            $orderSubtotal = $this->toDecimal($order->subtotal);

            if (bccomp($itemSubtotal, $orderSubtotal, 4) !== 0) {
                throw new RuntimeException(
                    "{$reference} subtotal does not match its item totals."
                );
            }

            if (bccomp($itemSubtotal, '0', 4) <= 0) {
                throw new RuntimeException(
                    "{$reference} total inventory value must be greater than zero."
                );
            }

            /*
             * Build the automatic Purchase Voucher.
             *
             * Debit:
             *   Product Inventory account(s)
             *
             * Credit:
             *   Purchase Clearing
             */
            $details = [];

            foreach ($inventoryDebits as $entry) {
                $account = $entry['account'];
                $amount = $entry['amount'];

                $details[] = [
                    'account_id' => $account->id,
                    'description' =>
                        "Inventory received - {$reference}",
                    'debit_amount' => $amount,
                    'credit_amount' => '0.0000',
                ];
            }

            $details[] = [
                'account_id' => $purchaseClearing->id,
                'description' =>
                    "Purchase Clearing - {$reference}",
                'debit_amount' => '0.0000',
                'credit_amount' => $itemSubtotal,
            ];

            /*
             * createAutoPosted() performs:
             *
             * Draft
             *   ↓
             * Approved
             *   ↓
             * LedgerPostingService::post()
             *
             * Therefore no separate ledger posting is performed here.
             */
            $transaction = app(VoucherService::class)->createAutoPosted([
                'company_id' => $companyId,
                'financial_year_id' => $financialYear->id,
                'voucher_type_id' => $voucherType->id,
                'voucher_date' => $order->order_date,
                'reference_type' => 'PurchaseOrder',
                'reference_id' => $order->id,
                'reference_number' => $reference,
                'narration' =>
                    "Purchase Order Receive #{$reference}",
                'details' => $details,
            ]);

            /*
             * Only after stock and accounting have both succeeded does
             * the controller change the PO status to Received.
             *
             * The outer transaction guarantees that a failure after this
             * point rolls back the stock and voucher together.
             */
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
