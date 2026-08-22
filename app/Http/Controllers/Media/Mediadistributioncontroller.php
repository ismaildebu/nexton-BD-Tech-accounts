<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\StoreMediaDistributionRequest;
use App\Models\MediaDistribution;
use App\Models\MediaDistributionItem;
use App\Models\MediaParty;
use App\Models\Publication;
use Illuminate\Support\Facades\DB;

/**
 * Phase 1 scaffold. Persists the header + line items with only the
 * mechanical per-line arithmetic needed to store consistent rows
 * (free qty, line total, amount). It does NOT yet:
 *   - write to newspaper_stock_movements
 *   - post to the ledger
 *   - enforce stock-availability checks
 * Those are deferred to a later phase.
 */
class MediaDistributionController extends Controller
{
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

    public function store(StoreMediaDistributionRequest $request)
    {
        $companyId = session('company_id');

        $distribution = DB::transaction(function () use ($request, $companyId) {
            $items = collect($request->validated('items'));

            $header = MediaDistribution::create([
                'company_id'         => $companyId,
                'publication_id'     => $request->validated('publication_id'),
                'distribution_date'  => $request->validated('distribution_date'),
                'status'             => MediaDistribution::STATUS_DRAFT,
                'notes'              => $request->validated('notes'),
                'created_by'         => auth()->id(),
            ]);

            $totalPaid = 0;
            $totalFree = 0;
            $totalAmount = 0;

            foreach ($items as $item) {
                $paid = (int) $item['paid_quantity'];
                $freePercentage = (float) ($item['free_percentage'] ?? 0);
                $free = (int) round($paid * $freePercentage / 100);
                $total = $paid + $free;
                $rate = (float) $item['rate'];
                $amount = round($paid * $rate, 2);

                MediaDistributionItem::create([
                    'media_distribution_id' => $header->id,
                    'media_party_id'        => $item['media_party_id'],
                    'paid_quantity'         => $paid,
                    'free_percentage'       => $freePercentage,
                    'free_quantity'         => $free,
                    'total_quantity'        => $total,
                    'rate'                  => $rate,
                    'amount'                => $amount,
                    'returned_quantity'     => 0,
                    'net_quantity'          => $total,
                ]);

                $totalPaid   += $paid;
                $totalFree   += $free;
                $totalAmount += $amount;
            }

            $header->update([
                'total_paid_quantity' => $totalPaid,
                'total_free_quantity' => $totalFree,
                'total_quantity'      => $totalPaid + $totalFree,
                'total_amount'        => $totalAmount,
            ]);

            return $header;
        });

        return redirect()->route('media.distributions.show', $distribution)
            ->with('success', 'Distribution recorded!');
    }

    public function show(MediaDistribution $distribution)
    {
        $distribution->load('items.party', 'publication');

        return view('media.distributions.show', compact('distribution'));
    }
}
