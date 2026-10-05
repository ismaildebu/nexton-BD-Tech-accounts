<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesPlanLimits;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Vendor;
use App\Services\PlanLimitService;
use App\Services\PurchaseOrderReceivingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PurchaseOrderController extends Controller
{
    use EnforcesPlanLimits;

    /**
     * Allowed Purchase Order status transitions.
     *
     * Draft     -> Confirmed | Cancelled
     * Confirmed -> Received  | Cancelled
     * Received  -> (terminal: stock has been added)
     * Cancelled -> (terminal)
     */
    private const ALLOWED_STATUS_TRANSITIONS = [
        'Draft'     => ['Confirmed', 'Cancelled'],
        'Confirmed' => ['Received', 'Cancelled'],
        'Received'  => [],
        'Cancelled' => [],
    ];

    public function __construct(
        private readonly PlanLimitService $planLimitService,
    ) {
    }

    public function index()
    {
        $company_id = session('company_id');
        $orders = PurchaseOrder::where('company_id', $company_id)
                    ->with('vendor')
                    ->latest()
                    ->get();
        return view('purchase-orders.index', compact('orders'));
    }

    public function create()
    {
        $company_id = session('company_id');
        $vendors = Vendor::where('company_id', $company_id)
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get();
        $products = Product::where('company_id', $company_id)
                        ->orderBy('name')
                        ->get();
        return view('purchase-orders.create', compact('vendors', 'products'));
    }

    public function store(Request $request)
    {
        $company_id = session('company_id');

        $validated = $request->validate([
            'vendor_id' => [
                'required',
                Rule::exists('vendors', 'id')->where(fn ($q) => $q->where('company_id', $company_id)),
            ],
            'order_date'    => 'required|date',
            'expected_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'tax'           => 'nullable|numeric|min:0',
            'discount'      => 'nullable|numeric|min:0',
            'notes'         => 'nullable|string',
            'items'         => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($q) => $q->where('company_id', $company_id)),
            ],
            'items.*.description' => 'nullable|string',
            'items.*.unit'        => 'nullable|string|max:50',
            'items.*.quantity'    => 'required|numeric|min:0.01',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ]);

        $this->enforcePlanLimit(
            $this->planLimitService,
            $company_id,
            'purchase_orders_monthly',
            PurchaseOrder::where('company_id', $company_id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        );

        $order = DB::transaction(function () use ($validated, $company_id) {
            /*
             * Lock the company row. It acts as a mutex so two requests
             * cannot generate the same PO number at the same time.
             */
            DB::table('companies')
                ->where('id', $company_id)
                ->lockForUpdate()
                ->first();

            $po_number = $this->nextPurchaseOrderNumber((int) $company_id);

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

            $tax      = $this->toDecimal($validated['tax'] ?? 0);
            $discount = $this->toDecimal($validated['discount'] ?? 0);

            $total = bcsub(
                bcadd($subtotal, $tax, 4),
                $discount,
                4
            );

            $order = PurchaseOrder::create([
                'company_id'    => $company_id,
                'vendor_id'     => $validated['vendor_id'],
                'po_number'     => $po_number,
                'order_date'    => $validated['order_date'],
                'expected_date' => $validated['expected_date'] ?? null,
                'status'        => 'Draft',
                'subtotal'      => $subtotal,
                'tax'           => $tax,
                'discount'      => $discount,
                'total'         => $total,
                'notes'         => $validated['notes'] ?? null,
            ]);

            // item_name is not taken from client input: after product_id is
            // validated, name/unit are snapshotted server-side from Product so
            // the item stays correctly linked to inventory (all products are
            // loaded at once to avoid N+1).
            $productIds = collect($validated['items'])->pluck('product_id');
            $products = Product::where('company_id', $company_id)
                            ->whereIn('id', $productIds)
                            ->get()
                            ->keyBy('id');

            foreach ($validated['items'] as $item) {
                $product = $products->get($item['product_id']);

                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'product_id'        => $product->id,
                    'item_name'         => $product->name,
                    'description'       => $item['description'] ?? null,
                    'quantity'          => $item['quantity'],
                    'unit'              => $item['unit'] ?? $product->unit ?? null,
                    'unit_price'        => $item['unit_price'],
                    'total'             => bcmul(
                        $this->toDecimal($item['quantity']),
                        $this->toDecimal($item['unit_price']),
                        4
                    ),
                ]);
            }

            /*
             * Re-calculate the totals from the values that were actually
             * stored (database columns may round long decimals), so the
             * order total always agrees with its stored items.
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
             * Prevent invalid negative totals.
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
                'total'    => $storedTotal,
            ]);

            return $order;
        });

        return redirect()->route('purchase-orders.show', $order)
                         ->with('success', 'Purchase Order created!');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load('vendor', 'items');
        return view('purchase-orders.show', compact('purchaseOrder'));
    }

    public function updateStatus(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'status' => 'required|in:Draft,Confirmed,Received,Cancelled',
        ]);

        $newStatus = $validated['status'];

        try {
            DB::transaction(function () use ($purchaseOrder, $newStatus): void {
                /*
                 * Lock the order and re-read its status so a stale model
                 * or a concurrent request cannot bypass the rules.
                 */
                $order = PurchaseOrder::query()
                    ->where('company_id', $purchaseOrder->company_id)
                    ->lockForUpdate()
                    ->findOrFail($purchaseOrder->id);

                $oldStatus = (string) $order->status;

                if ($oldStatus === $newStatus) {
                    throw new \RuntimeException(
                        "Purchase Order is already {$newStatus}."
                    );
                }

                $allowed = self::ALLOWED_STATUS_TRANSITIONS[$oldStatus] ?? [];

                if (! in_array($newStatus, $allowed, true)) {
                    throw new \RuntimeException(
                        "Invalid status change: {$oldStatus} to {$newStatus}."
                    );
                }

                /*
                 * Received: add the ordered quantities to stock.
                 * If anything fails the whole transaction rolls back and
                 * the order stays Confirmed.
                 */
                if ($newStatus === 'Received') {
                    app(PurchaseOrderReceivingService::class)->receive($order);
                }

                $order->update(['status' => $newStatus]);
            });

            return back()->with(
                'success',
                "Purchase Order status updated to {$newStatus}!"
            );
        } catch (\Exception $e) {
            return back()->with(
                'error',
                "Failed to update status: {$e->getMessage()}"
            );
        }
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        // Only Draft purchase orders can be deleted, so the link to a
        // Purchase Bill created downstream is never lost.
        if ($purchaseOrder->status !== 'Draft') {
            return back()->with('error', 'Only Draft purchase orders can be deleted.');
        }

        $purchaseOrder->delete();
        return redirect()->route('purchase-orders.index')
                         ->with('success', 'Purchase Order deleted!');
    }

    /**
     * Generate the next Purchase Order number for a company.
     *
     * Must be called inside a transaction that already holds the
     * company row lock.
     *
     * The sequence continues after the most recently created order
     * (deleted orders included), so deleting a Draft never causes an
     * already-used number to be handed out again.
     */
    private function nextPurchaseOrderNumber(int $companyId): string
    {
        $lastNumber = PurchaseOrder::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->orderByDesc('id')
            ->value('po_number');

        $sequence = preg_match('/(\d+)$/', (string) $lastNumber, $matches) === 1
            ? (int) $matches[1]
            : 0;

        do {
            $sequence++;

            $poNumber = 'PO-' . date('Ymd') . '-' . str_pad(
                (string) $sequence,
                4,
                '0',
                STR_PAD_LEFT
            );

            $exists = PurchaseOrder::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('po_number', $poNumber)
                ->exists();
        } while ($exists);

        return $poNumber;
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