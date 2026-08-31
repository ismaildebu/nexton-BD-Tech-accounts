<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Exceptions\InsufficientNewspaperStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\StoreMediaDistributionRequest;
use App\Models\MediaDistribution;
use App\Models\MediaDistributionItem;
use App\Models\MediaParty;
use App\Models\Publication;
use App\Services\Media\DistributionService;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;

/**
 * Phase 3: creation now goes entirely through DistributionService, which
 * resolves free % server-side (Party -> Publication -> System), verifies
 * available stock before writing anything, and records one aggregated
 * 'distribution' NewspaperStockMovement for the whole run — all inside a
 * single DB transaction. A confirmed MediaDistribution therefore always
 * has matching stock movement history; nothing here writes stock or
 * ledger entries outside that service.
 */
class MediaDistributionController extends Controller
{
    public function __construct(private readonly DistributionService $distributionService)
    {
    }

    public function index()
    {
        $distributions = MediaDistribution::where('company_id', session('company_id'))
            ->with('publication')
            ->latest('distribution_date')
            ->get();

        return view('media.distributions.index', compact('distributions'));
    }

    public function create()
    {
        $companyId = session('company_id');

        $publications = Publication::where('company_id', $companyId)->active()->get();
        $parties      = MediaParty::where('company_id', $companyId)->active()->get();

        return view('media.distributions.create', compact('publications', 'parties'));
    }

    public function store(StoreMediaDistributionRequest $request): RedirectResponse
    {
        $companyId   = session('company_id');
        $publication = Publication::where('company_id', $companyId)
            ->findOrFail($request->validated('publication_id'));

        try {
            $distribution = $this->distributionService->create(
                publication: $publication,
                distributionDate: $request->validated('distribution_date'),
                companyId: $companyId,
                createdBy: auth()->id(),
                items: $request->validated('items'),
                notes: $request->validated('notes'),
            );
        } catch (InsufficientNewspaperStockException $e) {
            return back()->withInput()->withErrors([
                'items' => "Distribution rejected — insufficient stock. Available: {$e->available}, Required: {$e->required}.",
            ]);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['items' => $e->getMessage()]);
        }

        return redirect()->route('media.distributions.show', $distribution)
            ->with('success', 'Distribution recorded and stock updated!');
    }

    public function show(MediaDistribution $distribution)
    {
        $distribution->load('items.party', 'publication');

        return view('media.distributions.show', compact('distribution'));
    }

    /**
     * Dispatch Sheet: one printable page listing every party in this
     * distribution run. Name/address/phone/type always come from the
     * MediaParty relation — never re-typed, never duplicated into
     * another table.
     */
    public function dispatchSheetPdf(MediaDistribution $distribution)
    {
        $this->authorize('print', $distribution);

        $distribution->loadMissing(['items.party', 'publication', 'company']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'media.distributions.dispatch-sheet-pdf',
            ['distribution' => $distribution]
        )->setPaper('a4');

        return $pdf->download("dispatch-sheet-{$distribution->id}.pdf");
    }

    /**
     * Bundle Slips: one slip per distribution item, all in a single PDF
     * (one slip per page) so a 100+ party run can be printed and cut
     * apart in one pass. Data comes from the same items+party relation
     * as the dispatch sheet — no separate slip table.
     */
    public function bundleSlipsPdf(MediaDistribution $distribution)
    {
        $this->authorize('print', $distribution);

        $distribution->loadMissing(['items.party', 'publication', 'company']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'media.distributions.bundle-slips-pdf',
            ['distribution' => $distribution, 'items' => $distribution->items]
        )->setPaper('a5');

        return $pdf->download("bundle-slips-{$distribution->id}.pdf");
    }

    /**
     * Reprint a single party's bundle slip (e.g. a lost/torn slip)
     * without regenerating the whole run's PDF.
     */
    public function bundleSlipPdf(MediaDistribution $distribution, MediaDistributionItem $item)
    {
        $this->authorize('print', $distribution);

        if ($item->media_distribution_id !== $distribution->id) {
            abort(404);
        }

        $item->loadMissing('party');
        $distribution->loadMissing('publication', 'company');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'media.distributions.bundle-slips-pdf',
            ['distribution' => $distribution, 'items' => collect([$item])]
        )->setPaper('a5');

        return $pdf->download("bundle-slip-{$distribution->id}-{$item->id}.pdf");
    }
}
