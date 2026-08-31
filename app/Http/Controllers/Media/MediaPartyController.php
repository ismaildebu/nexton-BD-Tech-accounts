<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\StoreMediaPartyRequest;
use App\Http\Requests\Media\UpdateMediaPartyRequest;
use App\Models\MediaParty;
use App\Models\Account;
use App\Models\Publication;
use App\Services\Media\FreePercentageResolver;
use Illuminate\Http\Request;

/**
 * Common interface for both Agent and Hawker — distinguished only by
 * `type`. There is NO relationship between Agent and Hawker anywhere
 * in this controller, matching the business rule.
 */
class MediaPartyController extends Controller
{
    public function __construct(private readonly FreePercentageResolver $freePercentageResolver)
    {
    }

    /**
     * NOTE: class-level abilities (viewAny/create) are enforced by the
     * 'can-permission:media-parties.*' route middleware and the
     * FormRequest's authorize() — see PublicationController for why
     * $this->authorize('create'/'viewAny', ModelClass::class) can't
     * work with a generic, class-string-driven ModulePolicy.
     */
    public function index(Request $request)
    {
        $parties = MediaParty::query()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->query('type')))
            ->orderBy('name')
            ->get();

        return view('media.parties.index', compact('parties'));
    }

    public function create()
    {
        $accounts = Account::query()->where('company_id', session('company_id'))->where('is_active', true)->where('account_type', 'Asset')->where('nature', 'Customer')->orderBy('account_name')->get();

        return view('media.parties.create', compact('accounts'));
    }

    public function store(StoreMediaPartyRequest $request)
    {
        $party = MediaParty::create($request->validated());

        return redirect()->route('media.parties.show', $party)
            ->with('success', 'Party created!');
    }

    /**
     * Shows the party plus, for each active publication, the
     * effective free percentage that will actually apply to them —
     * computed live via FreePercentageResolver, never stored
     * redundantly on the party or publication.
     */
    public function show(MediaParty $mediaParty)
    {
        $this->authorize('view', $mediaParty);

        $effectiveFreePercentages = Publication::active()->get()->map(function (Publication $publication) use ($mediaParty) {
            return [
                'publication' => $publication,
                'percentage'  => $this->freePercentageResolver->resolve($mediaParty, $publication),
                'source'      => $this->freePercentageResolver->source($mediaParty, $publication),
            ];
        });

        return view('media.parties.show', [
            'party' => $mediaParty,
            'effectiveFreePercentages' => $effectiveFreePercentages,
        ]);
    }

    public function edit(MediaParty $mediaParty)
    {
        $this->authorize('update', $mediaParty);

        $accounts = Account::query()->where('company_id', session('company_id'))->where('is_active', true)->where('account_type', 'Asset')->where('nature', 'Customer')->orderBy('account_name')->get();

        return view('media.parties.edit', ['party' => $mediaParty, 'accounts' => $accounts]);
    }

    public function update(UpdateMediaPartyRequest $request, MediaParty $mediaParty)
    {
        $this->authorize('update', $mediaParty);

        $mediaParty->update($request->validated());

        return redirect()->route('media.parties.show', $mediaParty)
            ->with('success', 'Party updated!');
    }

    public function destroy(MediaParty $mediaParty)
    {
        $this->authorize('delete', $mediaParty);

        $mediaParty->delete();

        return redirect()->route('media.parties.index')
            ->with('success', 'Party deleted!');
    }
}
