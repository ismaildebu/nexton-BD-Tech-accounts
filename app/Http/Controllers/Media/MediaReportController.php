<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\MediaCollection;
use App\Models\MediaDistribution;
use App\Models\MediaDistributionItem;
use App\Models\MediaParty;
use App\Models\MediaReturn;
use App\Models\MediaReturnItem;
use App\Models\NewspaperStockMovement;
use App\Models\Publication;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MediaReportController extends Controller
{
    // ─── Stock Report ─────────────────────────────────────────────────────────

    public function stockReport(Request $request)
    {
        $companyId    = session('company_id');
        $publications = Publication::where('company_id', $companyId)->active()->get();

        [$movements, $openingBalance, $runningBalance, $publication] =
            $this->buildStockData($request, $companyId);

        return view('media.reports.stock', compact(
            'publications', 'publication', 'movements', 'openingBalance', 'runningBalance'
        ));
    }

    public function stockReportPdf(Request $request)
    {
        $companyId = session('company_id');

        [$movements, $openingBalance, $runningBalance, $publication] =
            $this->buildStockData($request, $companyId);

        abort_if($publication === null, 422, 'Publication is required for PDF.');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('media.reports.stock-pdf', compact(
            'publication', 'movements', 'openingBalance', 'runningBalance', 'request'
        ))->setPaper('a4');

        return $pdf->download("stock-report-{$publication->code}.pdf");
    }

    private function buildStockData(Request $request, int $companyId): array
    {
        $publication    = null;
        $movements      = collect();
        $openingBalance = 0;
        $runningBalance = 0;

        if ($request->filled('publication_id')) {
            $publication = Publication::where('company_id', $companyId)
                ->findOrFail($request->publication_id);

            $query = NewspaperStockMovement::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('publication_id', $publication->id)
                ->orderBy('movement_date')
                ->orderBy('id');

            if ($request->filled('from_date')) {
                $openingBalance = (int) NewspaperStockMovement::withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->where('publication_id', $publication->id)
                    ->whereDate('movement_date', '<', $request->from_date)
                    ->sum('quantity');

                $query->whereDate('movement_date', '>=', $request->from_date);
            }

            if ($request->filled('to_date')) {
                $query->whereDate('movement_date', '<=', $request->to_date);
            }

            $runningBalance = $openingBalance;
            $movements = $query->get()->map(function ($mov) use (&$runningBalance) {
                $runningBalance += $mov->quantity;
                $mov->running_balance = $runningBalance;
                return $mov;
            });
        }

        return [$movements, $openingBalance, $runningBalance, $publication];
    }

    // ─── Distribution Summary ─────────────────────────────────────────────────

    public function distributionSummary(Request $request)
    {
        $companyId    = session('company_id');
        $publications = Publication::where('company_id', $companyId)->active()->get();

        [$distributions, $totals] = $this->buildDistributionData($request, $companyId);

        return view('media.reports.distribution-summary', compact(
            'publications', 'distributions', 'totals'
        ));
    }

    public function distributionSummaryPdf(Request $request)
    {
        $companyId = session('company_id');
        [$distributions, $totals] = $this->buildDistributionData($request, $companyId);
        $company = Company::find($companyId);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('media.reports.distribution-summary-pdf', compact(
            'distributions', 'totals', 'company', 'request'
        ))->setPaper('a4');

        return $pdf->download('distribution-summary.pdf');
    }

    private function buildDistributionData(Request $request, int $companyId): array
    {
        $distributions = collect();
        $totals = ['paid' => 0, 'free' => 0, 'total' => 0, 'amount' => 0.0];

        if ($request->filled('from_date') || $request->filled('publication_id')) {
            $query = MediaDistribution::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('status', MediaDistribution::STATUS_CONFIRMED)
                ->with('publication')
                ->orderBy('distribution_date');

            if ($request->filled('publication_id')) {
                $query->where('publication_id', $request->publication_id);
            }
            if ($request->filled('from_date')) {
                $query->whereDate('distribution_date', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $query->whereDate('distribution_date', '<=', $request->to_date);
            }

            $distributions = $query->get();
            $totals = [
                'paid'   => $distributions->sum('total_paid_quantity'),
                'free'   => $distributions->sum('total_free_quantity'),
                'total'  => $distributions->sum('total_quantity'),
                'amount' => (float) $distributions->sum('total_amount'),
            ];
        }

        return [$distributions, $totals];
    }

    // ─── Return Summary ───────────────────────────────────────────────────────

    public function returnSummary(Request $request)
    {
        $companyId    = session('company_id');
        $publications = Publication::where('company_id', $companyId)->active()->get();

        [$returns, $totals] = $this->buildReturnData($request, $companyId);

        return view('media.reports.return-summary', compact(
            'publications', 'returns', 'totals'
        ));
    }

    private function buildReturnData(Request $request, int $companyId): array
    {
        $returns = collect();
        $totals  = ['paid' => 0, 'free' => 0, 'total' => 0];

        if ($request->filled('from_date') || $request->filled('publication_id')) {
            $query = MediaReturn::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('status', MediaReturn::STATUS_CONFIRMED)
                ->with('publication')
                ->orderBy('return_date');

            if ($request->filled('publication_id')) {
                $query->where('publication_id', $request->publication_id);
            }
            if ($request->filled('from_date')) {
                $query->whereDate('return_date', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $query->whereDate('return_date', '<=', $request->to_date);
            }

            $returns = $query->get();
            $totals  = [
                'paid'  => $returns->sum('total_paid_return_quantity'),
                'free'  => $returns->sum('total_free_return_quantity'),
                'total' => $returns->sum('total_return_quantity'),
            ];
        }

        return [$returns, $totals];
    }

    // ─── Collection Summary ───────────────────────────────────────────────────

    public function collectionSummary(Request $request)
    {
        $companyId = session('company_id');
        $parties   = MediaParty::where('company_id', $companyId)->active()->get();

        [$collections, $totals] = $this->buildCollectionData($request, $companyId);

        return view('media.reports.collection-summary', compact(
            'parties', 'collections', 'totals'
        ));
    }

    private function buildCollectionData(Request $request, int $companyId): array
    {
        $collections = collect();
        $totals      = ['amount' => 0.0];

        if ($request->filled('from_date') || $request->filled('media_party_id')) {
            $query = MediaCollection::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->with('party', 'account')
                ->orderBy('collection_date');

            if ($request->filled('media_party_id')) {
                $query->where('media_party_id', $request->media_party_id);
            }
            if ($request->filled('from_date')) {
                $query->whereDate('collection_date', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $query->whereDate('collection_date', '<=', $request->to_date);
            }

            $collections = $query->get();
            $totals      = ['amount' => (float) $collections->sum('amount')];
        }

        return [$collections, $totals];
    }

    // ─── Party Ledger ─────────────────────────────────────────────────────────

    public function partyLedger(Request $request)
    {
        $companyId = session('company_id');
        $parties   = MediaParty::where('company_id', $companyId)->active()->get();

        [$party, $ledgerLines, $totals] = $this->buildPartyLedger($request, $companyId);

        return view('media.reports.party-ledger', compact(
            'parties', 'party', 'ledgerLines', 'totals'
        ));
    }

    public function partyLedgerPdf(Request $request)
    {
        $companyId = session('company_id');
        [$party, $ledgerLines, $totals] = $this->buildPartyLedger($request, $companyId);

        abort_if($party === null, 422, 'Party is required for PDF.');

        $company = Company::find($companyId);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('media.reports.party-ledger-pdf', compact(
            'party', 'ledgerLines', 'totals', 'company', 'request'
        ))->setPaper('a4');

        return $pdf->download("party-ledger-{$party->code}.pdf");
    }

    private function buildPartyLedger(Request $request, int $companyId): array
    {
        $party       = null;
        $ledgerLines = collect();
        $totals      = ['distributed' => 0, 'returned' => 0, 'net' => 0, 'amount' => 0.0, 'collected' => 0.0, 'balance' => 0.0];

        if (! $request->filled('media_party_id')) {
            return [$party, $ledgerLines, $totals];
        }

        $party = MediaParty::where('company_id', $companyId)
            ->findOrFail($request->media_party_id);

        
                    // Distribution items
        $distQuery = MediaDistributionItem::whereHas('distribution', fn ($q) => $q
            ->where('company_id', $companyId)
            ->where('status', MediaDistribution::STATUS_CONFIRMED))
            ->where('media_party_id', $party->id)
            ->with(['distribution' => fn ($q) => $q->with('publication')]);

        if ($request->filled('from_date')) {
            $distQuery->whereHas('distribution', fn ($q) => $q
                ->whereDate('distribution_date', '>=', $request->from_date));
        }
        if ($request->filled('to_date')) {
            $distQuery->whereHas('distribution', fn ($q) => $q
                ->whereDate('distribution_date', '<=', $request->to_date));
        }

        $distLines = $distQuery->get()->map(fn ($item) => (object) [
            'date'        => $item->distribution->distribution_date,
            'type'        => 'Distribution',
            'publication' => $item->distribution->publication?->name ?? '—',
            'paid'        => $item->paid_quantity,
            'free'        => $item->free_quantity,
            'total'       => $item->total_quantity,
            'returned'    => $item->returned_quantity,
            'net'         => $item->net_quantity,
            'dr_amount'   => (float) $item->amount,
            'cr_amount'   => 0.0,
            'ref'         => 'Dist #' . $item->distribution->id,
        ]);

        // Return items
        $returnQuery = MediaReturnItem::whereHas('mediaReturn', fn ($q) => $q
            ->where('company_id', $companyId)
            ->where('status', MediaReturn::STATUS_CONFIRMED))
            ->where('media_party_id', $party->id)
            ->with(['mediaReturn' => fn ($q) => $q->with('publication')]);

        if ($request->filled('from_date')) {
            $returnQuery->whereHas('mediaReturn', fn ($q) => $q
                ->whereDate('return_date', '>=', $request->from_date));
        }
        if ($request->filled('to_date')) {
            $returnQuery->whereHas('mediaReturn', fn ($q) => $q
                ->whereDate('return_date', '<=', $request->to_date));
        }

        $returnLines = $returnQuery->get()->map(fn ($item) => (object) [
            'date'        => $item->mediaReturn->return_date,
            'type'        => 'Return',
            'publication' => $item->mediaReturn->publication?->name ?? '—',
            'paid'        => 0,
            'free'        => 0,
            'total'       => 0,
            'returned'    => $item->total_return_quantity,
            'net'         => 0,
            'dr_amount'   => 0.0,
            'cr_amount'   => 0.0,
            'ref'         => 'Return #' . $item->mediaReturn->id,
        ]);

        // Collection lines
        $collQuery = MediaCollection::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('media_party_id', $party->id)
            ->with('account');

        if ($request->filled('from_date')) {
            $collQuery->whereDate('collection_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $collQuery->whereDate('collection_date', '<=', $request->to_date);
        }

        $collLines = $collQuery->get()->map(fn ($c) => (object) [
            'date'        => $c->collection_date,
            'type'        => 'Collection',
            'publication' => '—',
            'paid'        => 0,
            'free'        => 0,
            'total'       => 0,
            'returned'    => 0,
            'net'         => 0,
            'dr_amount'   => 0.0,
            'cr_amount'   => (float) $c->amount,
            'ref'         => 'Coll #' . $c->id . ' (' . $c->payment_method . ')',
        ]);

        $ledgerLines = $distLines->merge($returnLines)->merge($collLines)
            ->sortBy('date')->values();

        $totalDistributed = $distLines->sum('dr_amount');
        $totalCollected   = $collLines->sum('cr_amount');

        $totals = [
            'distributed' => $distLines->sum('total'),
            'returned'    => $distLines->sum('returned'),
            'net'         => $distLines->sum('net'),
            'amount'      => $totalDistributed,
            'collected'   => $totalCollected,
            'balance'     => $totalDistributed - $totalCollected,
        ];

        return [$party, $ledgerLines, $totals];
    }
}
