<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\StoreMediaCollectionRequest;
use App\Models\Account;
use App\Models\MediaCollection;
use App\Models\MediaParty;

/**
 * Phase 1 scaffold. Records the collection only — does NOT post to
 * the ledger yet (transaction_id stays null). Wiring this into
 * LedgerPostingService is a deliberate later step, once the
 * accounting-integration approach is confirmed.
 */
class MediaCollectionController extends Controller
{
    public function index()
    {
        $collections = MediaCollection::where('company_id', session('company_id'))
            ->with('party', 'account')
            ->latest('collection_date')
            ->get();

        return view('media.collections.index', compact('collections'));
    }

    public function create()
    {
        $companyId = session('company_id');

        $parties  = MediaParty::where('company_id', $companyId)->active()->get();
        $accounts = Account::where('company_id', $companyId)->active()->get();

        return view('media.collections.create', compact('parties', 'accounts'));
    }

    public function store(StoreMediaCollectionRequest $request)
    {
        $collection = MediaCollection::create([
            ...$request->validated(),
            'company_id' => session('company_id'),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('media.collections.show', $collection)
            ->with('success', 'Collection recorded!');
    }

    public function show(MediaCollection $collection)
    {
        $collection->load('party', 'account');

        return view('media.collections.show', compact('collection'));
    }
}
