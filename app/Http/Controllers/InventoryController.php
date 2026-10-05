<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesPlanLimits;
use App\Models\Account;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    use EnforcesPlanLimits;

    public function __construct(
        private readonly PlanLimitService $planLimitService,
    ) {
    }

    // =================== PRODUCTS ===================

    public function products()
    {
        $company_id = session('company_id');

        $products = Product::where('company_id', $company_id)
            ->with('stocks', 'inventoryAccount')
            ->orderBy('name')
            ->get();

        $low_stock = $products->filter(
            fn ($p) => $p->isLowStock() && $p->reorder_level > 0
        );

        return view(
            'inventory.products',
            compact('products', 'low_stock')
        );
    }

    public function createProduct()
    {
        $accounts = Account::query()
            ->where('company_id', session('company_id'))
            ->where('account_type', Account::TYPE_ASSET)
            ->where('nature', Account::NATURE_INVENTORY)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        return view(
            'inventory.create-product',
            compact('accounts')
        );
    }

    public function storeProduct(Request $request)
    {
        $company_id = (int) session('company_id');

        $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'reorder_level' => 'required|integer|min:0',
            'inventory_account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')
                    ->where(
                        fn ($q) => $q
                            ->where('company_id', $company_id)
                            ->where(
                                'account_type',
                                Account::TYPE_ASSET
                            )
                            ->where(
                                'nature',
                                Account::NATURE_INVENTORY
                            )
                            ->where('is_active', true)
                    ),
            ],
        ]);

        $this->enforcePlanLimit(
            $this->planLimitService,
            $company_id,
            'products',
            Product::where('company_id', $company_id)->count(),
        );

        Product::create([
            'company_id' => $company_id,
            'name' => $request->name,
            'sku' => $request->sku,
            'category' => $request->category,
            'unit' => $request->unit,
            'purchase_price' => $request->purchase_price,
            'sale_price' => $request->sale_price,
            'reorder_level' => $request->reorder_level,
            'description' => $request->description,
            'is_active' => true,
            'inventory_account_id' => $request->inventory_account_id,
        ]);

        return redirect()
            ->route('inventory.products')
            ->with('success', 'Product created!');
    }

    public function editProduct(Product $product)
    {
        abort_unless(
            (int) $product->company_id === (int) session('company_id'),
            404
        );

        $accounts = Account::query()
            ->where('company_id', session('company_id'))
            ->where('account_type', Account::TYPE_ASSET)
            ->where('nature', Account::NATURE_INVENTORY)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        return view(
            'inventory.edit-product',
            compact('product', 'accounts')
        );
    }

    public function updateProduct(
        Request $request,
        Product $product
    ) {
        $company_id = (int) session('company_id');

        abort_unless(
            (int) $product->company_id === $company_id,
            404
        );

        $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'reorder_level' => 'required|integer|min:0',
            'inventory_account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')
                    ->where(
                        fn ($q) => $q
                            ->where('company_id', $company_id)
                            ->where(
                                'account_type',
                                Account::TYPE_ASSET
                            )
                            ->where(
                                'nature',
                                Account::NATURE_INVENTORY
                            )
                            ->where('is_active', true)
                    ),
            ],
        ]);

        $product->update($request->only([
            'name',
            'sku',
            'category',
            'unit',
            'purchase_price',
            'sale_price',
            'reorder_level',
            'description',
            'inventory_account_id',
        ]));

        return redirect()
            ->route('inventory.products')
            ->with('success', 'Product updated!');
    }

    public function destroyProduct(Product $product)
    {
        abort_unless(
            (int) $product->company_id === (int) session('company_id'),
            404
        );

        $hasStockDeductedSalesOrder = SalesOrderItem::query()
            ->where('product_id', $product->id)
            ->whereHas('salesOrder', function ($query) use ($product): void {
                $query->where(
                    'company_id',
                    $product->company_id
                )->where(
                    'is_stock_deducted',
                    true
                );
            })
            ->exists();

        if ($hasStockDeductedSalesOrder) {
            return back()->with(
                'error',
                'This product cannot be deleted because it is used by a confirmed Sales Order with deducted stock.'
            );
        }

        $product->delete();

        return redirect()
            ->route('inventory.products')
            ->with('success', 'Product deleted!');
    }

    // =================== WAREHOUSES ===================

    public function warehouses()
    {
        $company_id = session('company_id');

        $warehouses = Warehouse::where(
            'company_id',
            $company_id
        )->get();

        return view(
            'inventory.warehouses',
            compact('warehouses')
        );
    }

    public function storeWarehouse(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Warehouse::create([
            'company_id' => session('company_id'),
            'name' => $request->name,
            'location' => $request->location,
        ]);

        return back()->with(
            'success',
            'Warehouse created!'
        );
    }

    public function destroyWarehouse(Warehouse $warehouse)
    {
        abort_unless(
            (int) $warehouse->company_id ===
                (int) session('company_id'),
            404
        );

        $hasStockDeductedSalesOrder = SalesOrder::query()
            ->where(
                'company_id',
                $warehouse->company_id
            )
            ->where(
                'warehouse_id',
                $warehouse->id
            )
            ->where(
                'is_stock_deducted',
                true
            )
            ->exists();

        if ($hasStockDeductedSalesOrder) {
            return back()->with(
                'error',
                'This warehouse cannot be deleted because it is used by a confirmed Sales Order with deducted stock.'
            );
        }

        $warehouse->delete();

        return back()->with(
            'success',
            'Warehouse deleted!'
        );
    }

    // =================== STOCK IN ===================

    public function stockIn()
    {
        $company_id = session('company_id');

        $products = Product::where(
            'company_id',
            $company_id
        )
            ->where('is_active', true)
            ->get();

        $warehouses = Warehouse::where(
            'company_id',
            $company_id
        )->get();

        return view(
            'inventory.stock-in',
            compact('products', 'warehouses')
        );
    }

    public function storeStockIn(Request $request)
    {
        $company_id = session('company_id');

        $request->validate([
            'warehouse_id' => [
                'required',
                Rule::exists('warehouses', 'id')
                    ->where(
                        fn ($q) => $q->where(
                            'company_id',
                            $company_id
                        )
                    ),
            ],
            'movement_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')
                    ->where(
                        fn ($q) => $q->where(
                            'company_id',
                            $company_id
                        )
                    ),
            ],
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use (
            $request,
            $company_id
        ): void {
            foreach ($request->items as $item) {
                $total_cost =
                    $item['quantity'] * $item['unit_cost'];

                StockMovement::create([
                    'company_id' => $company_id,
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $request->warehouse_id,
                    'type' => 'in',
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'total_cost' => $total_cost,
                    'reference' => $request->reference,
                    'reference_type' =>
                        $request->reference_type ?? 'manual',
                    'notes' => $request->notes,
                    'movement_date' =>
                        $request->movement_date,
                    'created_by' => auth()->id(),
                ]);

                StockMovement::adjustStock(
                    $item['product_id'],
                    $request->warehouse_id,
                    $item['quantity'],
                    'in'
                );
            }
        });

        return redirect()
            ->route('inventory.movements')
            ->with(
                'success',
                'Stock In recorded!'
            );
    }

    // =================== STOCK OUT ===================

    public function stockOut()
    {
        $company_id = session('company_id');

        $products = Product::where(
            'company_id',
            $company_id
        )
            ->where('is_active', true)
            ->get();

        $warehouses = Warehouse::where(
            'company_id',
            $company_id
        )->get();

        return view(
            'inventory.stock-out',
            compact('products', 'warehouses')
        );
    }

    public function storeStockOut(Request $request)
    {
        $company_id = session('company_id');

        $request->validate([
            'warehouse_id' => [
                'required',
                Rule::exists('warehouses', 'id')
                    ->where(
                        fn ($q) => $q->where(
                            'company_id',
                            $company_id
                        )
                    ),
            ],
            'movement_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')
                    ->where(
                        fn ($q) => $q->where(
                            'company_id',
                            $company_id
                        )
                    ),
            ],
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::transaction(function () use (
                $request,
                $company_id
            ): void {
                foreach ($request->items as $item) {
                    $product = Product::where(
                        'company_id',
                        $company_id
                    )
                        ->where(
                            'id',
                            $item['product_id']
                        )
                        ->firstOrFail();

                    $unit_cost =
                        (float) $product->purchase_price;

                    $total_cost =
                        (float) $item['quantity']
                        * $unit_cost;

                    /*
                     * adjustStock() performs the tenant-scoped
                     * row lock and authoritative stock availability
                     * check.
                     */
                    StockMovement::adjustStock(
                        $item['product_id'],
                        $request->warehouse_id,
                        $item['quantity'],
                        'out'
                    );

                    StockMovement::create([
                        'company_id' => $company_id,
                        'product_id' => $item['product_id'],
                        'warehouse_id' =>
                            $request->warehouse_id,
                        'type' => 'out',
                        'quantity' =>
                            $item['quantity'],
                        'unit_cost' => $unit_cost,
                        'total_cost' => $total_cost,
                        'reference' =>
                            $request->reference,
                        'reference_type' =>
                            $request->reference_type
                            ?? 'manual',
                        'notes' => $request->notes,
                        'movement_date' =>
                            $request->movement_date,
                        'created_by' => auth()->id(),
                    ]);
                }
            });
        } catch (\RuntimeException $e) {
            return back()
                ->withErrors([
                    'items' => $e->getMessage(),
                ])
                ->withInput();
        }

        return redirect()
            ->route('inventory.movements')
            ->with(
                'success',
                'Stock Out recorded!'
            );
    }

    // =================== MOVEMENTS ===================

    public function movements()
    {
        $company_id = session('company_id');

        $movements = StockMovement::where(
            'company_id',
            $company_id
        )
            ->with('product', 'warehouse')
            ->latest()
            ->paginate(20);

        return view(
            'inventory.movements',
            compact('movements')
        );
    }

    // =================== STOCK REPORT ===================

    public function stockReport()
    {
        $company_id = session('company_id');

        $products = Product::where(
            'company_id',
            $company_id
        )
            ->with(['stocks.warehouse'])
            ->get();

        $warehouses = Warehouse::where(
            'company_id',
            $company_id
        )->get();

        $total_value = $products->sum(
            fn ($p) => $p->stockValue()
        );

        $low_stock = $products->filter(
            fn ($p) =>
                $p->isLowStock()
                && $p->reorder_level > 0
        );

        return view(
            'inventory.stock-report',
            compact(
                'products',
                'warehouses',
                'total_value',
                'low_stock'
            )
        );
    }
}