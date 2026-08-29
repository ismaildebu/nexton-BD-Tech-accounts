<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesPlanLimits;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\ProductStock;
use App\Models\StockMovement;
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
                        ->with('stocks')
                        ->orderBy('name')
                        ->get();

        $low_stock = $products->filter(fn($p) => $p->isLowStock() && $p->reorder_level > 0);

        return view('inventory.products', compact('products', 'low_stock'));
    }

    public function createProduct()
    {
        return view('inventory.create-product');
    }

    public function storeProduct(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'unit'           => 'required|string|max:50',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price'     => 'required|numeric|min:0',
            'reorder_level'  => 'required|integer|min:0',
        ]);

        $this->enforcePlanLimit(
            $this->planLimitService,
            session('company_id'),
            'products',
            Product::where('company_id', session('company_id'))->count(),
        );

        Product::create([
            'company_id'     => session('company_id'),
            'name'           => $request->name,
            'sku'            => $request->sku,
            'category'       => $request->category,
            'unit'           => $request->unit,
            'purchase_price' => $request->purchase_price,
            'sale_price'     => $request->sale_price,
            'reorder_level'  => $request->reorder_level,
            'description'    => $request->description,
            'is_active'      => true,
        ]);

        return redirect()->route('inventory.products')
                         ->with('success', 'Product created!');
    }

    public function editProduct(Product $product)
    {
        return view('inventory.edit-product', compact('product'));
    }

    public function updateProduct(Request $request, Product $product)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'unit'           => 'required|string|max:50',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price'     => 'required|numeric|min:0',
            'reorder_level'  => 'required|integer|min:0',
        ]);

        $product->update($request->only([
            'name', 'sku', 'category', 'unit',
            'purchase_price', 'sale_price',
            'reorder_level', 'description',
        ]));

        return redirect()->route('inventory.products')
                         ->with('success', 'Product updated!');
    }

    public function destroyProduct(Product $product)
    {
        $product->delete();
        return redirect()->route('inventory.products')
                         ->with('success', 'Product deleted!');
    }

    // =================== WAREHOUSES ===================

    public function warehouses()
    {
        $company_id = session('company_id');
        $warehouses = Warehouse::where('company_id', $company_id)->get();
        return view('inventory.warehouses', compact('warehouses'));
    }

    public function storeWarehouse(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        Warehouse::create([
            'company_id' => session('company_id'),
            'name'       => $request->name,
            'location'   => $request->location,
        ]);
        return back()->with('success', 'Warehouse created!');
    }

    public function destroyWarehouse(Warehouse $warehouse)
    {
        $warehouse->delete();
        return back()->with('success', 'Warehouse deleted!');
    }

    // =================== STOCK IN ===================

    public function stockIn()
    {
        $company_id = session('company_id');
        $products   = Product::where('company_id', $company_id)->where('is_active', true)->get();
        $warehouses = Warehouse::where('company_id', $company_id)->get();
        return view('inventory.stock-in', compact('products', 'warehouses'));
    }

    public function storeStockIn(Request $request)
    {
        $company_id = session('company_id');

        // ✅ Fix: আগে warehouse_id/product_id শুধু existence check হতো,
        // company scope ছাড়া — ফলে অন্য company-র warehouse-এ stock ঢুকিয়ে
        // দেওয়া যেত (cross-tenant inventory corruption)।
        $request->validate([
            'warehouse_id' => [
                'required',
                Rule::exists('warehouses', 'id')->where(fn ($q) => $q->where('company_id', $company_id)),
            ],
            'movement_date'  => 'required|date',
            'items'          => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($q) => $q->where('company_id', $company_id)),
            ],
            'items.*.quantity'    => 'required|numeric|min:0.01',
            'items.*.unit_cost'   => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $company_id) {
            foreach ($request->items as $item) {
                $total_cost = $item['quantity'] * $item['unit_cost'];

                StockMovement::create([
                    'company_id'    => $company_id,
                    'product_id'    => $item['product_id'],
                    'warehouse_id'  => $request->warehouse_id,
                    'type'          => 'in',
                    'quantity'      => $item['quantity'],
                    'unit_cost'     => $item['unit_cost'],
                    'total_cost'    => $total_cost,
                    'reference'     => $request->reference,
                    'reference_type'=> $request->reference_type ?? 'manual',
                    'notes'         => $request->notes,
                    'movement_date' => $request->movement_date,
                    'created_by'    => auth()->id(),
                ]);

                StockMovement::adjustStock(
                    $item['product_id'],
                    $request->warehouse_id,
                    $item['quantity'],
                    'in'
                );
            }
        });

        return redirect()->route('inventory.movements')
                         ->with('success', 'Stock In recorded!');
    }

    // =================== STOCK OUT ===================

    public function stockOut()
    {
        $company_id = session('company_id');
        $products   = Product::where('company_id', $company_id)->where('is_active', true)->get();
        $warehouses = Warehouse::where('company_id', $company_id)->get();
        return view('inventory.stock-out', compact('products', 'warehouses'));
    }

    public function storeStockOut(Request $request)
    {
        $company_id = session('company_id');

        // ✅ Fix: warehouse_id/product_id এখন company-scoped — অন্য company-র
        // warehouse থেকে stock বের করা যাবে না।
        $request->validate([
            'warehouse_id' => [
                'required',
                Rule::exists('warehouses', 'id')->where(fn ($q) => $q->where('company_id', $company_id)),
            ],
            'movement_date' => 'required|date',
            'items'         => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($q) => $q->where('company_id', $company_id)),
            ],
            'items.*.quantity'   => 'required|numeric|min:0.01',
        ]);

        try {
            DB::transaction(function () use ($request, $company_id) {
                foreach ($request->items as $item) {
                    // ✅ Fix: lockForUpdate() — আগে check-then-decrement এর মাঝে
                    // race condition ছিল, দুইটা concurrent request একসাথে
                    // stock available দেখে উভয়েই decrement করলে negative stock
                    // হয়ে যেতে পারত।
                    $stock = ProductStock::where('product_id', $item['product_id'])
                                ->where('warehouse_id', $request->warehouse_id)
                                ->lockForUpdate()
                                ->value('quantity') ?? 0;

                    if ($stock < $item['quantity']) {
                        $product = Product::find($item['product_id']);
                        throw new \RuntimeException("Insufficient stock for {$product->name}. Available: {$stock}");
                    }

                    $product   = Product::find($item['product_id']);
                    $unit_cost = $product->purchase_price;
                    $total_cost = $item['quantity'] * $unit_cost;

                    StockMovement::create([
                        'company_id'    => $company_id,
                        'product_id'    => $item['product_id'],
                        'warehouse_id'  => $request->warehouse_id,
                        'type'          => 'out',
                        'quantity'      => $item['quantity'],
                        'unit_cost'     => $unit_cost,
                        'total_cost'    => $total_cost,
                        'reference'     => $request->reference,
                        'reference_type'=> $request->reference_type ?? 'manual',
                        'notes'         => $request->notes,
                        'movement_date' => $request->movement_date,
                        'created_by'    => auth()->id(),
                    ]);

                    StockMovement::adjustStock(
                        $item['product_id'],
                        $request->warehouse_id,
                        $item['quantity'],
                        'out'
                    );
                }
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        return redirect()->route('inventory.movements')
                         ->with('success', 'Stock Out recorded!');
    }

    // =================== MOVEMENTS ===================

    public function movements()
    {
        $company_id = session('company_id');
        $movements  = StockMovement::where('company_id', $company_id)
                        ->with('product', 'warehouse')
                        ->latest()
                        ->paginate(20);
        return view('inventory.movements', compact('movements'));
    }

    // =================== STOCK REPORT ===================

    public function stockReport()
    {
        $company_id = session('company_id');
        $products   = Product::where('company_id', $company_id)
                        ->with(['stocks.warehouse'])
                        ->get();
        $warehouses = Warehouse::where('company_id', $company_id)->get();
        $total_value = $products->sum(fn($p) => $p->stockValue());
        $low_stock   = $products->filter(fn($p) => $p->isLowStock() && $p->reorder_level > 0);

        return view('inventory.stock-report', compact('products', 'warehouses', 'total_value', 'low_stock'));
    }
}