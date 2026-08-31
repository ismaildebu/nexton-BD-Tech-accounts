<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\ApprovePrintPlanRequest;
use App\Http\Requests\Media\RejectPrintPlanRequest;
use App\Http\Requests\Media\StorePrintPlanRequest;
use App\Models\Publication;
use App\Models\PrintPlan;
use App\Services\Media\PrintPlanningService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;

class PrintPlanController extends Controller
{
    public function __construct(private readonly PrintPlanningService $printPlanningService)
    {
    }

    /**
     * NOTE: class-level abilities (viewAny/create) are enforced by the
     * 'can-permission:media-print-planning.*' route middleware and the
     * FormRequest's authorize() — see PublicationController for why
     * $this->authorize('create'/'viewAny', ModelClass::class) can't
     * work with a generic, class-string-driven ModulePolicy.
     */
    public function index()
    {
        $plans = PrintPlan::with('publication')
            ->latest('plan_date')
            ->get();

        return view('media.print-plans.index', compact('plans'));
    }

    public function create()
    {
        $publications = Publication::active()->get();

        return view('media.print-plans.create', compact('publications'));
    }

    /**
     * Runs the calculation via PrintPlanningService::createPlan() and
     * persists a Draft plan. All arithmetic happens in the service —
     * this method only forwards validated input.
     */
    public function store(StorePrintPlanRequest $request): RedirectResponse
    {
        $publication = Publication::findOrFail($request->validated('publication_id'));

        $plan = $this->printPlanningService->createPlan(
            publication: $publication,
            planDate: Carbon::parse($request->validated('plan_date')),
            companyId: session('company_id'),
            createdBy: auth()->id(),
        );

        // A manual adjustment submitted at creation time (optional —
        // most plans are approved separately via approve()).
        if ($request->filled('adjusted_quantity')) {
            $plan->update([
                'adjusted_quantity' => $request->validated('adjusted_quantity'),
                'adjustment_reason' => $request->validated('adjustment_reason'),
            ]);
        }

        return redirect()->route('media.print-plans.show', $plan)
            ->with('success', 'Print plan calculated and saved as Draft.');
    }

    public function show(PrintPlan $printPlan)
    {
        $this->authorize('view', $printPlan);

        $printPlan->load('publication', 'creator', 'approver', 'printOrders');

        return view('media.print-plans.show', ['plan' => $printPlan]);
    }

    /**
     * Approve the plan, optionally overriding the recommended quantity.
     * The resulting final_quantity is what PrintOrderService::createFromPlan()
     * will read when the order is placed.
     */
    public function approve(ApprovePrintPlanRequest $request, PrintPlan $printPlan): RedirectResponse
    {
        $this->authorize('approve', $printPlan);

        try {
            $this->printPlanningService->approve(
                plan: $printPlan,
                approver: auth()->user(),
                adjustedQuantity: $request->validated('adjusted_quantity'),
                adjustmentReason: $request->validated('adjustment_reason'),
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['adjusted_quantity' => $e->getMessage()])->withInput();
        }

        return redirect()->route('media.print-plans.show', $printPlan)
            ->with('success', 'Print plan approved.');
    }

    public function reject(RejectPrintPlanRequest $request, PrintPlan $printPlan): RedirectResponse
    {
        $this->authorize('approve', $printPlan);

        try {
            $this->printPlanningService->reject($printPlan, auth()->user(), $request->validated('reason'));
        } catch (\Throwable $e) {
            return back()->withErrors(['reason' => $e->getMessage()])->withInput();
        }

        return redirect()->route('media.print-plans.show', $printPlan)
            ->with('success', 'Print plan rejected.');
    }
}
