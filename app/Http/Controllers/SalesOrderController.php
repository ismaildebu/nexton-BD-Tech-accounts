<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesPlanLimits;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Warehouse;
use App\Services\PlanLimitService;
use App\Services\SalesOrderAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SalesOrderController extends Controller
{
    use EnforcesPlanLimits;

    /**
     * Allowed Sales Order status transitions.
     *
     * Draft     -> Confirmed | Cancelled
     * Confirmed -> Delivered | Cancelled
     * Delivered -> Cancelled
     * Cancelled -> (terminal)
     */
    private const ALLOWED_STATUS_TRANSITIONS = [
        'Draft'     => ['Confirmed', 'Cancelled'],
        'Confirmed' => ['Delivered', 'Cancelled'],
        'Delivered' => ['Cancelled'],
        'Cancelled' => [],
    ];

    public function __construct(
        private readonly PlanLimitService $planLimitService,
        private readonly SalesOrderAccountingService $accountingService,
    ) {
    }

    public function index()
    {
        $company_id = session('company_id');

        $orders = SalesOrder::where('company_id', $company_id)
            ->with('customer')
            ->latest()
            ->get();

        return view('sales-orders.index', compact('orders'));
    }

    public function create()
    {
        $company_id = session('company_id');

        $customers = Customer::where('company_id', $company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::where('company_id', $company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $warehouses = Warehouse::where('company_id', $company_id)
            ->orderBy('name')
            ->get();

        return view(
            'sales-orders.create',
            compact('customers', 'products', 'warehouses')
        );
    }

    public function store(Request $request)
    {
        $company_id = session('company_id');

        $validated = $request->validate([
            'customer_id' => [
                'required',
                Rule::exists('customers', 'id')
                    ->where('company_id', $company_id),
            ],

            'warehouse_id' => [
                'required',
                Rule::exists('warehouses', 'id')
                    ->where('company_id', $company_id),
            ],

            'order_date' => 'required|date',

            'delivery_date' => [
                'nullable',
                'date',
                'after_or_equal:order_date',
            ],

            'tax' => 'nullable|numeric|min:0',

            'discount' => 'nullable|numeric|min:0',

            'notes' => 'nullable|string',

            'items' => 'required|array|min:1',

            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')
                    ->where('company_id', $company_id),
            ],

            'items.*.item_name' => 'required|string|max:255',

            'items.*.description' => 'nullable|string',

            'items.*.quantity' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'items.*.unit' => 'nullable|string|max:50',

            'items.*.unit_price' => [
                'required',
                'numeric',
                'min:0',
            ],
        ]);

        $this->enforcePlanLimit(
            $this->planLimitService,
            $company_id,
            'sales_orders_monthly',
            SalesOrder::where('company_id', $company_id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        );

        $order = DB::transaction(function () use (
            $validated,
            $company_id
        ) {
            /*
             * Lock the company row. It acts as a mutex so two requests
             * cannot generate the same Sales Order number at the same time.
             */
            DB::table('companies')
                ->where('id', $company_id)
                ->lockForUpdate()
                ->first();

            $so_number = $this->nextSalesOrderNumber((int) $company_id);

            /*
             * All money is calculated with bcmath decimal strings.
             */
            $subtotal = '0.0000';

            foreach ($validated['items'] as $item) {
                $subtotal = bcadd(
                    $subtotal,
                    bcmul(
                        $this->toDecimal($item['quantity']),
                        $this->toDecimal($item['unit_price']),
                        4
                    ),
                    4
                );
            }

            $tax = $this->toDecimal($validated['tax'] ?? 0);
            $discount = $this->toDecimal($validated['discount'] ?? 0);

            $total = bcsub(
                bcadd($subtotal, $tax, 4),
                $discount,
                4
            );

            $order = SalesOrder::create([
                'company_id' => $company_id,
                'customer_id' => $validated['customer_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'so_number' => $so_number,
                'order_date' => $validated['order_date'],
                'delivery_date' => $validated['delivery_date'] ?? null,
                'status' => 'Draft',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $total,
                'notes' => $validated['notes'] ?? null,
                'is_accounted' => false,
                'is_stock_deducted' => false,
            ]);

            foreach ($validated['items'] as $item) {
                SalesOrderItem::create([
                    'sales_order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'item_name' => $item['item_name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? null,
                    'unit_price' => $item['unit_price'],
                    'total' => bcmul(
                        $this->toDecimal($item['quantity']),
                        $this->toDecimal($item['unit_price']),
                        4
                    ),
                ]);
            }

            /*
             * Re-calculate the totals from the values that were actually
             * stored (database columns may round long decimals). The
             * accounting service uses the stored values, so the order
             * total must be derived from them too.
             */
            $order->refresh();

            $storedSubtotal = '0.0000';

            foreach (
                $order->items()->get(['quantity', 'unit_price']) as $line
            ) {
                $storedSubtotal = bcadd(
                    $storedSubtotal,
                    bcmul(
                        $this->toDecimal($line->quantity),
                        $this->toDecimal($line->unit_price),
                        4
                    ),
                    4
                );
            }

            $storedTotal = bcsub(
                bcadd(
                    $storedSubtotal,
                    $this->toDecimal($order->tax),
                    4
                ),
                $this->toDecimal($order->discount),
                4
            );

            /*
             * Prevent invalid negative Sales Order totals.
             * Throwing inside the transaction rolls everything back.
             */
            if (bccomp($storedTotal, '0', 4) < 0) {
                throw ValidationException::withMessages([
                    'discount' => 'Discount cannot be greater than the '
                        . 'order subtotal plus tax.',
                ]);
            }

            $order->update([
                'subtotal' => $storedSubtotal,
                'total' => $storedTotal,
            ]);

            return $order;
        });

        return redirect()
            ->route('sales-orders.show', $order)
            ->with('success', 'Sales Order created!');
    }

    public function show(SalesOrder $salesOrder)
    {
        $salesOrder->load(
            'customer',
            'warehouse',
            'items.product'
        );

        return view(
            'sales-orders.show',
            compact('salesOrder')
        );
    }

    public function updateStatus(
        Request $request,
        SalesOrder $salesOrder
    ) {
        $validated = $request->validate([
            'status' => 'required|in:Draft,Confirmed,Delivered,Cancelled',
        ]);

        $newStatus = $validated['status'];

        try {
            DB::transaction(function () use ($salesOrder, $newStatus): void {
                /*
                 * Lock the order and re-read its status so a stale model
                 * or a concurrent request cannot bypass the transition rules.
                 */
                $order = SalesOrder::query()
                    ->where('company_id', $salesOrder->company_id)
                    ->lockForUpdate()
                    ->findOrFail($salesOrder->id);

                $oldStatus = (string) $order->status;

                if ($oldStatus === $newStatus) {
                    throw new \RuntimeException(
                        "Sales Order is already {$newStatus}."
                    );
                }

                $allowed = self::ALLOWED_STATUS_TRANSITIONS[$oldStatus] ?? [];

                if (! in_array($newStatus, $allowed, true)) {
                    throw new \RuntimeException(
                        "Invalid status change: {$oldStatus} to {$newStatus}."
                    );
                }

                if ($newStatus === 'Confirmed') {
                    $this->accountingService->onConfirmed($order);
                }

                if ($newStatus === 'Cancelled') {
                    $this->accountingService->onCancelled($order);
                }

                $order->update([
                    'status' => $newStatus,
                ]);
            });

            return back()->with(
                'success',
                "Sales Order status updated to {$newStatus}!"
            );
        } catch (\Exception $e) {
            return back()->with(
                'error',
                "Failed to update status: {$e->getMessage()}"
            );
        }
    }

    public function destroy(SalesOrder $salesOrder)
    {
        /*
         * Only Draft Sales Orders can be deleted.
         */
        if ($salesOrder->status !== 'Draft') {
            return back()->with(
                'error',
                'Only Draft orders can be deleted. Cancel it instead.'
            );
        }

        $salesOrder->delete();

        return redirect()
            ->route('sales-orders.index')
            ->with(
                'success',
                'Sales Order deleted!'
            );
    }

    /**
     * Generate the next Sales Order number for a company.
     *
     * Must be called inside a transaction that already holds the
     * company row lock.
     *
     * The sequence continues after the most recently created order
     * (deleted orders included), so deleting a Draft never causes an
     * already-used number to be handed out again.
     */
    private function nextSalesOrderNumber(int $companyId): string
    {
        $lastNumber = SalesOrder::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->orderByDesc('id')
            ->value('so_number');

        $sequence = preg_match('/(\d+)$/', (string) $lastNumber, $matches) === 1
            ? (int) $matches[1]
            : 0;

        do {
            $sequence++;

            $soNumber = 'SO-' . date('Ymd') . '-' . str_pad(
                (string) $sequence,
                4,
                '0',
                STR_PAD_LEFT
            );

            $exists = SalesOrder::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('so_number', $soNumber)
                ->exists();
        } while ($exists);

        return $soNumber;
    }

    /**
     * Normalize a request/database value into a plain decimal string
     * that bcmath can safely use.
     */
    private function toDecimal(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '0.0000';
        }

        $string = trim((string) $value);

        if (preg_match('/^\d+(\.\d+)?$/', $string) === 1) {
            return bcadd($string, '0', 4);
        }

        return number_format((float) $value, 4, '.', '');
    }
}