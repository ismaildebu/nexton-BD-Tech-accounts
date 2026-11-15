<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\StoreMediaCollectionRequest;
use App\Models\Account;
use App\Models\MediaCollection;
use App\Models\MediaParty;
use App\Services\Media\MediaAccountingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Records the collection and posts its accounting transaction atomically.
 * A collection is not considered successfully recorded unless its ledger
 * posting also succeeds.
 */
class MediaCollectionController extends Controller
{
    public function __construct(
        private readonly MediaAccountingService $accountingService,
    ) {
    }

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

    public function store(StoreMediaCollectionRequest $request): RedirectResponse
    {
        $companyId = (int) session('company_id');

        try {
            $collection = DB::transaction(function () use ($request, $companyId): MediaCollection {
                $collection = MediaCollection::create([
                    ...$request->validated(),
                    'company_id' => $companyId,
                    'created_by' => auth()->id(),
                ]);

                $this->accountingService->postCollection($collection);

                return $collection->fresh(['party', 'account', 'transaction']);
            });
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors([
                'collection' => $e->getMessage(),
            ]);
        }

        return redirect()->route('media.collections.show', $collection)
            ->with('success', 'Collection recorded and posted to the ledger!');
    }

    public function show(MediaCollection $collection)
    {
        $collection->load('party', 'account');

        return view('media.collections.show', compact('collection'));
    }
}
