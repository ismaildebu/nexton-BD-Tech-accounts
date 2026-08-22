<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\StoreMediaReturnRequest;
use App\Models\MediaReturn;
use App\Models\MediaReturnItem;
use App\Models\Publication;
use Illuminate\Support\Facades\DB;

/**
 * Phase 1 scaffold. Persists header + line items with Paid/Free
 * return tracked separately per line, as required. Does NOT yet
 * reconcile against MediaDistributionItem.returned_quantity/net_quantity
 * or write newspaper_stock_movements — deferred to a later phase.
 */
class MediaReturnController extends Controller
{
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
        $publications = Publication::where('company_id', session('company_id'))->active()->get();

        return view('media.returns.create', compact('publications'));
    }

    public function store(StoreMediaReturnRequest $request)
    {
        $companyId = session('company_id');

        $return = DB::transaction(function () use ($request, $companyId) {
            $items = collect($request->validated('items'));

            $header = MediaReturn::create([
                'company_id'             => $companyId,
                'publication_id'         => $request->validated('publication_id'),
                'media_distribution_id'  => $request->validated('media_distribution_id'),
                'return_date'            => $request->validated('return_date'),
                'status'                 => MediaReturn::STATUS_DRAFT,
                'notes'                  => $request->validated('notes'),
                'created_by'             => auth()->id(),
            ]);

            $totalPaidReturn = 0;
            $totalFreeReturn = 0;

            foreach ($items as $item) {
                $paidReturn = (int) $item['paid_return_quantity'];
                $freeReturn = (int) $item['free_return_quantity'];

                MediaReturnItem::create([
                    'media_return_id'        => $header->id,
                    'media_party_id'         => $item['media_party_id'],
                    'paid_return_quantity'   => $paidReturn,
                    'free_return_quantity'   => $freeReturn,
                    'total_return_quantity'  => $paidReturn + $freeReturn,
                ]);

                $totalPaidReturn += $paidReturn;
                $totalFreeReturn += $freeReturn;
            }

            $header->update([
                'total_paid_return_quantity' => $totalPaidReturn,
                'total_free_return_quantity' => $totalFreeReturn,
                'total_return_quantity'      => $totalPaidReturn + $totalFreeReturn,
            ]);

            return $header;
        });

        return redirect()->route('media.returns.show', $return)
            ->with('success', 'Return recorded!');
    }

    public function show(MediaReturn $return)
    {
        $return->load('items.party', 'publication');

        return view('media.returns.show', compact('return'));
    }
}
