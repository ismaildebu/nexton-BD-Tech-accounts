<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\StoreMediaReturnRequest;
use App\Models\MediaDistribution;
use App\Models\MediaReturn;
use App\Models\Publication;
use App\Services\Media\ReturnService;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;

/**
 * Phase 4: creation now delegates to ReturnService which:
 *   - validates per-party return quantity against distribution net_quantity
 *   - updates MediaDistributionItem.returned_quantity / net_quantity
 *   - writes a 'return' NewspaperStockMovement (stock comes back)
 *   - everything in one DB transaction
 */
class MediaReturnController extends Controller
{
    public function __construct(private readonly ReturnService $returnService)
    {
    }

    public function index()
    {
        $returns = MediaReturn::where('company_id', session('company_id'))
            ->with('publication')
            ->latest('return_date')
            ->get();

        return view('media.returns.index', compact('returns'));
    }

    public function create()
    {
        $companyId    = session('company_id');
        $publications = Publication::where('company_id', $companyId)->active()->get();
        $distributions = MediaDistribution::where('company_id', $companyId)
            ->where('status', MediaDistribution::STATUS_CONFIRMED)
            ->with('publication')
            ->latest('distribution_date')
            ->get();

        return view('media.returns.create', compact('publications', 'distributions'));
    }

    public function store(StoreMediaReturnRequest $request): RedirectResponse
    {
        $companyId   = session('company_id');
        $publication = Publication::where('company_id', $companyId)
            ->findOrFail($request->validated('publication_id'));

        try {
            $return = $this->returnService->create(
                publication: $publication,
                returnDate: $request->validated('return_date'),
                companyId: $companyId,
                createdBy: auth()->id(),
                items: $request->validated('items'),
                distributionId: $request->validated('media_distribution_id'),
                notes: $request->validated('notes'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['items' => $e->getMessage()]);
        }

        return redirect()->route('media.returns.show', $return)
            ->with('success', 'Return recorded and stock updated!');
    }

    public function show(MediaReturn $return)
    {
        $return->load('items.party', 'publication', 'distribution');

        return view('media.returns.show', compact('return'));
    }
}
