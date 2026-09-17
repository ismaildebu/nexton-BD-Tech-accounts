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

class MediaDistributionController extends Controller
{
    public function __construct(
        private readonly DistributionService $distributionService
    ) {
    }

    public function index()
    {
        $companyId = session('company_id');

        $distributions = MediaDistribution::where('company_id', $companyId)
            ->with('publication')
            ->latest('distribution_date')
            ->latest('id')
            ->get();

        return view('media.distributions.index', compact('distributions'));
    }

    public function create()
    {
        $companyId = session('company_id');

        /*
         * Active publications belonging to the current company.
         */
        $publications = Publication::where('company_id', $companyId)
            ->active()
            ->orderBy('name')
            ->get();

        /*
         * Active parties belonging to the current company.
         */
        $parties = MediaParty::where('company_id', $companyId)
            ->active()
            ->orderBy('name')
            ->get();

        /*
         * Get the latest distribution for each publication.
         *
         * This is used only as a template for the new distribution.
         * The previous distribution is NEVER modified.
         */
        $latestDistributions = MediaDistribution::where('company_id', $companyId)
            ->with('items')
            ->latest('distribution_date')
            ->latest('id')
            ->get()
            ->unique('publication_id')
            ->keyBy('publication_id');

        /*
         * Convert the latest distributions into a simple
         * JSON-safe array before sending it to Blade.
         *
         * Keeping this transformation here prevents complicated
         * nested PHP expressions inside Blade @json().
         */
        $latestDistributionData = [];

        foreach ($latestDistributions as $distribution) {
            $distributionDate = $distribution->distribution_date;

            if ($distributionDate !== null) {
                $distributionDate = substr((string) $distributionDate, 0, 10);
            }

            $latestDistributionData[$distribution->publication_id] = [
                'id' => $distribution->id,
                'publication_id' => $distribution->publication_id,
                'distribution_date' => $distributionDate,
                'items' => $distribution->items
                    ->map(function (MediaDistributionItem $item): array {
                        return [
                            'media_party_id' => $item->media_party_id,
                            'paid_quantity' => $item->paid_quantity,
                            'rate' => $item->rate,
                        ];
                    })
                    ->values()
                    ->all(),
            ];
        }

        return view('media.distributions.create', compact(
            'publications',
            'parties',
            'latestDistributionData'
        ));
    }

    public function store(StoreMediaDistributionRequest $request): RedirectResponse
    {
        $companyId = session('company_id');

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
        } catch (InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors([
                    'items' => $e->getMessage(),
                ]);
        }

        return redirect()
            ->route('media.distributions.show', $distribution)
            ->with(
                'success',
                'Distribution demand saved as draft. Create the Print Order, receive the newspapers, then confirm the distribution.'
            );
    }

    public function confirm(MediaDistribution $distribution): RedirectResponse
    {
        $companyId = (int) session('company_id');

        try {
            $distribution = $this->distributionService->confirm(
                distribution: $distribution,
                companyId: $companyId,
                confirmedBy: (int) auth()->id(),
            );
        } catch (InsufficientNewspaperStockException $e) {
            return back()->withErrors([
                'stock' => "Distribution cannot be confirmed. Available: {$e->available}, Required: {$e->required}.",
            ]);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors([
                'distribution' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('media.distributions.show', $distribution)
            ->with(
                'success',
                'Distribution confirmed and newspaper stock updated.'
            );
    }

    public function show(MediaDistribution $distribution)
    {
        $distribution->load(
            'items.party',
            'publication'
        );

        return view(
            'media.distributions.show',
            compact('distribution')
        );
    }

    public function dispatchSheetPdf(MediaDistribution $distribution)
    {
        $this->authorize('print', $distribution);

        $distribution->loadMissing([
            'items.party',
            'publication',
            'company',
        ]);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'media.distributions.dispatch-sheet-pdf',
            [
                'distribution' => $distribution,
            ]
        )->setPaper('a4');

        return $pdf->download(
            "dispatch-sheet-{$distribution->id}.pdf"
        );
    }

    public function bundleSlipsPdf(MediaDistribution $distribution)
    {
        $this->authorize('print', $distribution);

        $distribution->loadMissing([
            'items.party',
            'publication',
            'company',
        ]);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'media.distributions.bundle-slips-pdf',
            [
                'distribution' => $distribution,
                'items' => $distribution->items,
            ]
        )->setPaper('a5');

        return $pdf->download(
            "bundle-slips-{$distribution->id}.pdf"
        );
    }

    public function bundleSlipPdf(
        MediaDistribution $distribution,
        MediaDistributionItem $item
    ) {
        $this->authorize('print', $distribution);

        /*
         * Make sure the requested item actually belongs
         * to the selected distribution.
         */
        if ($item->media_distribution_id !== $distribution->id) {
            abort(404);
        }

        $item->loadMissing('party');

        $distribution->loadMissing([
            'publication',
            'company',
        ]);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'media.distributions.bundle-slips-pdf',
            [
                'distribution' => $distribution,
                'items' => collect([$item]),
            ]
        )->setPaper('a5');

        return $pdf->download(
            "bundle-slip-{$distribution->id}-{$item->id}.pdf"
        );
    }
}