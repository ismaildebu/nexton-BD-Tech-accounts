<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesPlanLimits;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Vendor;
use App\Services\PlanLimitService;
use App\Services\PurchaseOrderReceivingService;
use App\Support\AmountInWords;
use Barryvdh\DomPDF\Facade\Pdf;
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
        $companyId = session('company_id');

        $orders = PurchaseOrder::where('company_id', $companyId)
            ->with('vendor')
            ->latest()
            ->get();

        return view('purchase-orders.index', compact('orders'));
    }

    public function create()
    {
        $companyId = session('company_id');

        $vendors = Vendor::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        return view('purchase-orders.create', compact('vendors', 'products'));
    }

    public function store(Request $request)
    {
        $companyId = session('company_id');

        $validated = $request->validate([
            'vendor_id' => [
                'required',
                Rule::exists('vendors', 'id')
                    ->where(fn ($q) => $q->where('company_id', $companyId)),
            ],

            'order_date' => 'required|date',

            'expected_date' => [
                'nullable',
                'date',
                'after_or_equal:order_date',
            ],

            /*
             * Tax and discount are percentages.
             */
            'tax' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],

            'discount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],

            'notes' => 'nullable|string',

            'items' => 'required|array|min:1',

            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')
                    ->where(fn ($q) => $q->where('company_id', $companyId)),
            ],

            'items.*.description' => 'nullable|string',

            'items.*.unit' => 'nullable|string|max:50',

            'items.*.quantity' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'items.*.unit_price' => [
                'required',
                'numeric',
                'min:0',
            ],
        ]);

        $this->enforcePlanLimit(
            $this->planLimitService,
            $companyId,
            'purchase_orders_monthly',
            PurchaseOrder::where('company_id', $companyId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        );

        $order = DB::transaction(function () use ($validated, $companyId) {

            /*
             * Lock the company row. It acts as a mutex so two requests
             * cannot generate the same PO number at the same time.
             */
            DB::table('companies')
                ->where('id', $companyId)
                ->lockForUpdate()
                ->first();

            $poNumber = $this->nextPurchaseOrderNumber($companyId);

            /*
             * Calculate subtotal from the submitted line items.
             * All money is calculated with BCMath decimal strings.
             */
            $subtotal = '0.0000';

            foreach ($validated['items'] as $item) {
                $lineTotal = bcmul(
                    $this->toDecimal($item['quantity']),
                    $this->toDecimal($item['unit_price']),
                    4
                );

                $subtotal = bcadd(
                    $subtotal,
                    $lineTotal,
                    4
                );
            }

            /*
             * Tax and discount are percentages from the request.
             *
             * Example:
             * subtotal = 10000
             * tax      = 5%
             * tax      = 500
             *
             * discount = 2%
             * discount = 200
             */
            $taxPercent = $this->toDecimal(
                $validated['tax'] ?? 0
            );

            $discountPercent = $this->toDecimal(
                $validated['discount'] ?? 0
            );

            $taxAmount = bcdiv(
                bcmul($subtotal, $taxPercent, 8),
                '100',
                4
            );

            $discountAmount = bcdiv(
                bcmul($subtotal, $discountPercent, 8),
                '100',
                4
            );

            $total = bcsub(
                bcadd($subtotal, $taxAmount, 4),
                $discountAmount,
                4
            );

            /*
             * Since both percentages are capped at 100%, the total
             * cannot become negative through normal validation.
             * Keep the explicit check as a database integrity safeguard.
             */
            if (bccomp($total, '0', 4) < 0) {
                throw ValidationException::withMessages([
                    'discount' => 'Discount cannot be greater than the order subtotal.',
                ]);
            }

            $order = PurchaseOrder::create([
                'company_id' => $companyId,
                'vendor_id' => $validated['vendor_id'],
                'po_number' => $poNumber,
                'order_date' => $validated['order_date'],
                'expected_date' => $validated['expected_date'] ?? null,
                'status' => 'Draft',

                /*
                 * Database columns remain monetary amounts.
                 */
                'subtotal' => $subtotal,
                'tax' => $taxAmount,
                'discount' => $discountAmount,
                'total' => $total,

                'notes' => $validated['notes'] ?? null,
            ]);

            /*
             * item_name is not taken from client input.
             * Product name/unit are snapshotted server-side.
             *
             * Products are loaded at once to avoid N+1 queries.
             */
            $productIds = collect($validated['items'])
                ->pluck('product_id');

            $products = Product::where('company_id', $companyId)
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');

            foreach ($validated['items'] as $item) {
                $product = $products->get($item['product_id']);

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => 'One or more selected products are invalid.',
                    ]);
                }

                $lineTotal = bcmul(
                    $this->toDecimal($item['quantity']),
                    $this->toDecimal($item['unit_price']),
                    4
                );

                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'product_id' => $product->id,
                    'item_name' => $product->name,
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? $product->unit ?? null,
                    'unit_price' => $item['unit_price'],
                    'total' => $lineTotal,
                ]);
            }

            /*
             * Re-calculate totals from values actually stored in the database.
             *
             * This ensures the header totals agree with the stored item values
             * even when database decimal columns round long decimals.
             */
            $order->refresh();

            $storedSubtotal = '0.0000';

            foreach (
                $order->items()->get(['quantity', 'unit_price']) as $line
            ) {
                $storedLineTotal = bcmul(
                    $this->toDecimal($line->quantity),
                    $this->toDecimal($line->unit_price),
                    4
                );

                $storedSubtotal = bcadd(
                    $storedSubtotal,
                    $storedLineTotal,
                    4
                );
            }

            /*
             * Recalculate monetary tax and discount from the original
             * percentages after the stored subtotal is known.
             */
            $storedTaxAmount = bcdiv(
                bcmul($storedSubtotal, $taxPercent, 8),
                '100',
                4
            );

            $storedDiscountAmount = bcdiv(
                bcmul($storedSubtotal, $discountPercent, 8),
                '100',
                4
            );

            $storedTotal = bcsub(
                bcadd(
                    $storedSubtotal,
                    $storedTaxAmount,
                    4
                ),
                $storedDiscountAmount,
                4
            );

            /*
             * Prevent invalid negative totals.
             * Throwing inside the transaction rolls everything back.
             */
            if (bccomp($storedTotal, '0', 4) < 0) {
                throw ValidationException::withMessages([
                    'discount' => 'Discount cannot be greater than the order subtotal.',
                ]);
            }

            $order->update([
                'subtotal' => $storedSubtotal,
                'tax' => $storedTaxAmount,
                'discount' => $storedDiscountAmount,
                'total' => $storedTotal,
            ]);

            return $order;
        });

        return redirect()
            ->route('purchase-orders.show', $order)
            ->with('success', 'Purchase Order created!');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->ensureCompanyAccess($purchaseOrder);

        $purchaseOrder->load('vendor', 'items');

        /*
         * The database stores tax/discount as monetary amounts.
         * Derive the original percentage for display.
         */
        $taxPercentage = $this->percentageFromAmount(
            $purchaseOrder->tax,
            $purchaseOrder->subtotal
        );

        $discountPercentage = $this->percentageFromAmount(
            $purchaseOrder->discount,
            $purchaseOrder->subtotal
        );

        $amountInWords = AmountInWords::taka(
            (string) $purchaseOrder->total,
            'en'
        );

        return view('purchase-orders.show', compact(
            'purchaseOrder',
            'taxPercentage',
            'discountPercentage',
            'amountInWords',
        ));
    }

    /**
     * Generate Purchase Order PDF.
     */
    public function pdf(PurchaseOrder $purchaseOrder)
    {
        $this->ensureCompanyAccess($purchaseOrder);

        $purchaseOrder->load('vendor', 'items');

        $taxPercentage = $this->percentageFromAmount(
            $purchaseOrder->tax,
            $purchaseOrder->subtotal
        );

        $discountPercentage = $this->percentageFromAmount(
            $purchaseOrder->discount,
            $purchaseOrder->subtotal
        );

        $amountInWords = AmountInWords::taka(
            (string) $purchaseOrder->total,
            'en'
        );

        $pdf = Pdf::loadView('purchase-orders.pdf', [
            'purchaseOrder' => $purchaseOrder,
            'taxPercentage' => $taxPercentage,
            'discountPercentage' => $discountPercentage,
            'amountInWords' => $amountInWords,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download(
            $purchaseOrder->po_number . '.pdf'
        );
    }

    public function updateStatus(
        Request $request,
        PurchaseOrder $purchaseOrder
    ) {
        $this->ensureCompanyAccess($purchaseOrder);

        $validated = $request->validate([
            'status' => 'required|in:Draft,Confirmed,Received,Cancelled',
        ]);

        $newStatus = $validated['status'];

        try {
            DB::transaction(function () use (
                $purchaseOrder,
                $newStatus
            ): void {

                /*
                 * Lock the order and re-read its status so a stale model
                 * or concurrent request cannot bypass the rules.
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
                 * If anything fails the whole transaction rolls back.
                 */
                if ($newStatus === 'Received') {
                    app(PurchaseOrderReceivingService::class)
                        ->receive($order);
                }

                $order->update([
                    'status' => $newStatus,
                ]);
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
        $this->ensureCompanyAccess($purchaseOrder);

        /*
         * Only Draft purchase orders can be deleted, so the link to a
         * Purchase Bill created downstream is never lost.
         */
        if ($purchaseOrder->status !== 'Draft') {
            return back()->with(
                'error',
                'Only Draft purchase orders can be deleted.'
            );
        }

        $purchaseOrder->delete();

        return redirect()
            ->route('purchase-orders.index')
            ->with('success', 'Purchase Order deleted!');
    }

    /**
     * Generate the next Purchase Order number for a company.
     *
     * Must be called inside a transaction that already holds the
     * company row lock.
     */
    private function nextPurchaseOrderNumber(int $companyId): string
    {
        $lastNumber = PurchaseOrder::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->orderByDesc('id')
            ->value('po_number');

        $sequence = preg_match(
            '/(\d+)$/',
            (string) $lastNumber,
            $matches
        ) === 1
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
     * Convert a stored monetary amount into its percentage of subtotal.
     */
    private function percentageFromAmount(
        mixed $amount,
        mixed $subtotal
    ): string {
        $amount = $this->toDecimal($amount);
        $subtotal = $this->toDecimal($subtotal);

        if (bccomp($subtotal, '0', 4) === 0) {
            return '0.00';
        }

        return bcdiv(
            bcmul($amount, '100', 8),
            $subtotal,
            2
        );
    }

    /**
     * Ensure the Purchase Order belongs to the active company.
     */
    private function ensureCompanyAccess(
        PurchaseOrder $purchaseOrder
    ): void {
        abort_unless(
            (int) $purchaseOrder->company_id === (int) session('company_id'),
            404
        );
    }

    /**
     * Normalize a request/database value into a plain decimal string
     * that BCMath can safely use.
     */
    private function toDecimal(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '0.0000';
        }

        $string = trim((string) $value);

        if (preg_match(
            '/^\d+(\.\d+)?$/',
            $string
        ) === 1) {
            return bcadd($string, '0', 4);
        }

        return number_format(
            (float) $value,
            4,
            '.',
            ''
        );
    }
}
