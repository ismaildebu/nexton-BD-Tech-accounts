<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\StorePrintOrderFromPlanRequest;
use App\Http\Requests\Media\StorePrintOrderRequest;
use App\Http\Requests\Media\UpdatePrintOrderRequest;
use App\Http\Requests\Media\UpdatePrintOrderStatusRequest;
use App\Models\MediaDistribution;
use App\Models\PrintOrder;
use App\Models\PrintPlan;
use App\Models\Publication;
use App\Models\Vendor;
use App\Services\Media\PrintOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PrintOrderController extends Controller
{
    public function __construct(
        private readonly PrintOrderService $printOrderService
    ) {
    }

    /**
     * Display Print Orders.
     */
    public function index()
    {
        $orders = PrintOrder::with(
            'publication',
            'vendor',
            'printPlan'
        )
            ->latest('order_date')
            ->get();

        return view(
            'media.print-orders.index',
            compact('orders')
        );
    }

    /**
     * Show the demand-driven Print Order creation form.
     *
     * Operational flow:
     *
     * Distribution Demand
     *        ↓
     * Demand Quantity
     *        ↓
     * Buffer %
     *        ↓
     * Buffer Quantity
     *        ↓
     * Final Print Quantity
     */
    public function create()
    {
        $companyId = session('company_id');

        /*
         * Only active publications belonging to the
         * current company are loaded through the
         * company-scoped Publication model.
         */
        $publications = Publication::active()
            ->get();

        /*
         * Vendors are explicitly company-scoped.
         */
        $vendors = Vendor::where(
            'company_id',
            $companyId
        )
            ->where('is_active', true)
            ->get();

        /*
         * Get the latest Distribution Demand for each publication
         * belonging to the current company. Draft and Confirmed
         * distributions are valid demand sources; Cancelled ones
         * are ignored.
         */
        $latestDistributions = MediaDistribution::query()
            ->where('company_id', $companyId)
            ->whereIn('status', [
                MediaDistribution::STATUS_DRAFT,
                MediaDistribution::STATUS_CONFIRMED,
            ])
            ->orderByDesc('distribution_date')
            ->orderByDesc('id')
            ->get()
            ->unique('publication_id')
            ->keyBy('publication_id');

        return view(
            'media.print-orders.create',
            compact(
                'publications',
                'vendors',
                'latestDistributions'
            )
        );
    }

    /**
     * Store a demand-driven Print Order.
     *
     * Demand and final quantity are calculated server-side
     * inside PrintOrderService.
     */
    public function store(
        StorePrintOrderRequest $request
    ): RedirectResponse {
        $validated = $request->validated();

        /*
         * Publication is already company-scoped by
         * StorePrintOrderRequest validation.
         */
        $publication = Publication::findOrFail(
            $validated['publication_id']
        );

        try {
            $order = $this->printOrderService->create(
                publication: $publication,
                companyId: (int) session('company_id'),
                createdBy: (int) auth()->id(),
                data: $validated,
            );
        } catch (RuntimeException $e) {
            return back()
                ->withErrors([
                    'publication_id' => $e->getMessage(),
                ])
                ->withInput();
        }

        return redirect()
            ->route(
                'media.print-orders.show',
                $order
            )
            ->with(
                'success',
                "Print order {$order->order_number} created!"
            );
    }

    /**
     * Store a Print Order from an Approved Print Plan.
     *
     * This remains available as an optional / legacy flow.
     *
     * The normal operational Print Order flow is the
     * demand-driven store() method above.
     */
    public function storeFromPlan(
        StorePrintOrderFromPlanRequest $request,
        PrintPlan $printPlan
    ): RedirectResponse {
        try {
            $order = $this->printOrderService->createFromPlan(
                plan: $printPlan,
                companyId: (int) session('company_id'),
                createdBy: (int) auth()->id(),
                data: $request->validated(),
            );
        } catch (RuntimeException $e) {
            return back()
                ->withErrors([
                    'print_plan_id' => $e->getMessage(),
                ])
                ->withInput();
        }

        return redirect()
            ->route(
                'media.print-orders.show',
                $order
            )
            ->with(
                'success',
                "Print order {$order->order_number} created from the approved plan!"
            );
    }

    /**
     * Display a Print Order.
     */
    public function show(PrintOrder $printOrder)
    {
        $this->authorize(
            'view',
            $printOrder
        );

        $printOrder->load(
            'publication',
            'vendor',
            'printPlan',
            'creator'
        );

        return view(
            'media.print-orders.show',
            ['order' => $printOrder]
        );
    }

    /**
     * Show the Draft Print Order edit form.
     *
     * Demand/buffer/final quantity are intentionally not
     * recalculated here. They are fixed at order creation.
     */
    public function edit(PrintOrder $printOrder)
    {
        $this->authorize(
            'update',
            $printOrder
        );

        abort_unless(
            $printOrder->status === PrintOrder::STATUS_DRAFT,
            422,
            'Only a Draft print order can be edited.'
        );

        $companyId = session('company_id');

        $vendors = Vendor::where(
            'company_id',
            $companyId
        )
            ->where('is_active', true)
            ->get();

        return view(
            'media.print-orders.edit',
            [
                'order' => $printOrder,
                'vendors' => $vendors,
            ]
        );
    }

    /**
     * Update a Draft Print Order.
     */
    public function update(
        UpdatePrintOrderRequest $request,
        PrintOrder $printOrder
    ): RedirectResponse {
        $this->authorize(
            'update',
            $printOrder
        );

        abort_unless(
            $printOrder->status === PrintOrder::STATUS_DRAFT,
            422,
            'Only a Draft print order can be edited.'
        );

        $printOrder->update(
            $request->validated()
        );

        return redirect()
            ->route(
                'media.print-orders.show',
                $printOrder
            )
            ->with(
                'success',
                'Print order updated!'
            );
    }

    /**
     * Draft -> Ordered.
     *
     * Confirms the order is placed with the press.
     */
    public function approve(
        PrintOrder $printOrder
    ): RedirectResponse {
        $this->authorize(
            'approve',
            $printOrder
        );

        try {
            $this->printOrderService->approve(
                $printOrder
            );
        } catch (RuntimeException $e) {
            return back()
                ->withErrors([
                    'status' => $e->getMessage(),
                ]);
        }

        return redirect()
            ->route(
                'media.print-orders.show',
                $printOrder
            )
            ->with(
                'success',
                'Print order approved and marked as Ordered.'
            );
    }

    /**
     * Update Print Order status.
     *
     * Workflow:
     *
     * Ordered
     *    ↓
     * Printing
     *    ↓
     * Printed
     *    ↓
     * Received
     *
     * Or:
     *
     * Draft / Ordered / Printing
     *            ↓
     *        Cancelled
     */
    public function updateStatus(
        UpdatePrintOrderStatusRequest $request,
        PrintOrder $printOrder
    ): RedirectResponse {
        $this->authorize(
            'updateStatus',
            $printOrder
        );

        try {
            match ($request->validated('status')) {
                PrintOrder::STATUS_PRINTING =>
                    $this->printOrderService->markPrinting(
                        $printOrder
                    ),

                PrintOrder::STATUS_PRINTED =>
                    $this->printOrderService->markPrinted(
                        $printOrder,
                        (int) $request->validated(
                            'printed_quantity'
                        )
                    ),

                PrintOrder::STATUS_RECEIVED =>
                    $this->printOrderService->markReceived(
                        $printOrder,
                        (int) $request->validated(
                            'received_quantity'
                        )
                    ),

                PrintOrder::STATUS_CANCELLED =>
                    $this->printOrderService->cancel(
                        $printOrder
                    ),
            };
        } catch (RuntimeException $e) {
            return back()
                ->withErrors([
                    'status' => $e->getMessage(),
                ]);
        }

        return redirect()
            ->route(
                'media.print-orders.show',
                $printOrder
            )
            ->with(
                'success',
                'Print order status updated.'
            );
    }

    /**
     * Download Print Order PDF.
     *
     * Reuses the existing DomPDF setup.
     */
    public function downloadPdf(
        PrintOrder $printOrder
    ) {
        $this->authorize(
            'print',
            $printOrder
        );

        $printOrder->loadMissing([
            'publication',
            'vendor',
            'printPlan',
            'creator',
            'company',
        ]);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'media.print-orders.pdf',
            ['order' => $printOrder]
        )->setPaper('a4');

        return $pdf->download(
            $printOrder->order_number . '.pdf'
        );
    }
}