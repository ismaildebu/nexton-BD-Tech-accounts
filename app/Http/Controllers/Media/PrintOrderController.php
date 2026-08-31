<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\StorePrintOrderFromPlanRequest;
use App\Http\Requests\Media\StorePrintOrderRequest;
use App\Http\Requests\Media\UpdatePrintOrderRequest;
use App\Http\Requests\Media\UpdatePrintOrderStatusRequest;
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
    public function __construct(private readonly PrintOrderService $printOrderService)
    {
    }

    /**
     * NOTE: class-level abilities (viewAny/create) are enforced by the
     * 'can-permission:media-print-orders.*' route middleware and the
     * FormRequest's authorize() — see PublicationController for why
     * $this->authorize('create'/'viewAny', ModelClass::class) can't
     * work with a generic, class-string-driven ModulePolicy.
     */
    public function index()
    {
        $orders = PrintOrder::with('publication', 'vendor', 'printPlan')
            ->latest('order_date')
            ->get();

        return view('media.print-orders.index', compact('orders'));
    }

    public function create(Request $request)
    {
        $companyId = session('company_id');

        $publications = Publication::active()->get();
        $vendors      = Vendor::where('company_id', $companyId)->where('is_active', true)->get();
        $approvedPlans = PrintPlan::where('status', PrintPlan::STATUS_APPROVED)
            ->whereDoesntHave('printOrders')
            ->with('publication')
            ->get();

        // Optional: ?plan=<id> preselects an approved plan (e.g. linked
        // from the Print Plan show page) so the order form can be
        // rendered in "from plan" mode with ordered_quantity locked to
        // the plan's final_quantity.
        $selectedPlan = null;
        if ($request->filled('plan')) {
            $selectedPlan = $approvedPlans->firstWhere('id', (int) $request->query('plan'));
        }

        return view('media.print-orders.create', compact('publications', 'vendors', 'approvedPlans', 'selectedPlan'));
    }

    /**
     * Ad-hoc order — no plan behind it, ordered_quantity hand-entered.
     */
    public function store(StorePrintOrderRequest $request): RedirectResponse
    {
        $publication = Publication::findOrFail($request->validated('publication_id'));

        $order = $this->printOrderService->create(
            publication: $publication,
            companyId: session('company_id'),
            createdBy: auth()->id(),
            data: $request->safe()->except('publication_id'),
        );

        return redirect()->route('media.print-orders.show', $order)
            ->with('success', "Print order {$order->order_number} created!");
    }

    /**
     * Order created from an Approved Print Plan — ordered_quantity
     * always comes from the plan's final_quantity.
     */
    public function storeFromPlan(StorePrintOrderFromPlanRequest $request, PrintPlan $printPlan): RedirectResponse
    {
        try {
            $order = $this->printOrderService->createFromPlan(
                plan: $printPlan,
                companyId: session('company_id'),
                createdBy: auth()->id(),
                data: $request->validated(),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['print_plan_id' => $e->getMessage()])->withInput();
        }

        return redirect()->route('media.print-orders.show', $order)
            ->with('success', "Print order {$order->order_number} created from the approved plan!");
    }

    public function show(PrintOrder $printOrder)
    {
        $this->authorize('view', $printOrder);

        $printOrder->load('publication', 'vendor', 'printPlan', 'creator');

        return view('media.print-orders.show', ['order' => $printOrder]);
    }

    public function edit(PrintOrder $printOrder)
    {
        $this->authorize('update', $printOrder);

        abort_unless($printOrder->status === PrintOrder::STATUS_DRAFT, 422, 'Only a Draft print order can be edited.');

        $companyId = session('company_id');
        $vendors = Vendor::where('company_id', $companyId)->where('is_active', true)->get();

        return view('media.print-orders.edit', ['order' => $printOrder, 'vendors' => $vendors]);
    }

    public function update(UpdatePrintOrderRequest $request, PrintOrder $printOrder): RedirectResponse
    {
        $this->authorize('update', $printOrder);

        abort_unless($printOrder->status === PrintOrder::STATUS_DRAFT, 422, 'Only a Draft print order can be edited.');

        $printOrder->update($request->validated());

        return redirect()->route('media.print-orders.show', $printOrder)
            ->with('success', 'Print order updated!');
    }

    /**
     * Draft -> Ordered. Confirms the order is placed with the press.
     */
    public function approve(PrintOrder $printOrder): RedirectResponse
    {
        $this->authorize('approve', $printOrder);

        try {
            $this->printOrderService->approve($printOrder);
        } catch (RuntimeException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('media.print-orders.show', $printOrder)
            ->with('success', 'Print order approved and marked as Ordered.');
    }

    /**
     * Ordered -> Printing -> Printed -> Received, or -> Cancelled.
     */
    public function updateStatus(UpdatePrintOrderStatusRequest $request, PrintOrder $printOrder): RedirectResponse
    {
        $this->authorize('updateStatus', $printOrder);

        try {
            match ($request->validated('status')) {
                PrintOrder::STATUS_PRINTING  => $this->printOrderService->markPrinting($printOrder),
                PrintOrder::STATUS_PRINTED   => $this->printOrderService->markPrinted($printOrder, (int) $request->validated('printed_quantity')),
                PrintOrder::STATUS_RECEIVED  => $this->printOrderService->markReceived($printOrder, (int) $request->validated('received_quantity')),
                PrintOrder::STATUS_CANCELLED => $this->printOrderService->cancel($printOrder),
            };
        } catch (RuntimeException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('media.print-orders.show', $printOrder)
            ->with('success', 'Print order status updated.');
    }

    /**
     * Print Order PDF — reuses the existing barryvdh/laravel-dompdf
     * setup already used by VoucherController::downloadPdf(); no new
     * PDF library is introduced.
     */
    public function downloadPdf(PrintOrder $printOrder)
    {
        $this->authorize('print', $printOrder);

        $printOrder->loadMissing(['publication', 'vendor', 'printPlan', 'creator', 'company']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'media.print-orders.pdf',
            ['order' => $printOrder]
        )->setPaper('a4');

        return $pdf->download($printOrder->order_number . '.pdf');
    }
}
