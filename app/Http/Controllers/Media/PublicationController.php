<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\StorePublicationRequest;
use App\Http\Requests\Media\UpdatePublicationRequest;
use App\Models\Publication;
use App\Models\Account;

/**
 * Complete CRUD for Publication. Company isolation is enforced two
 * ways at once: the BelongsToCompany global scope on the model (so
 * route-model binding 404s on a cross-company id) and the ModulePolicy
 * gate on every action (so a user without the permission never gets
 * this far regardless of company).
 */
class PublicationController extends Controller
{
    /**
     * NOTE: class-level abilities (viewAny/create) are enforced by the
     * 'can-permission:media-publications.*' route middleware (see
     * routes/media.php) and by the FormRequest's authorize(), NOT by
     * $this->authorize() here. Laravel's Gate strips a leading string
     * argument before calling a policy method (it assumes the string
     * was only there to pick the policy class), so
     * $this->authorize('create', Publication::class) can never
     * actually deliver the model class to ModulePolicy::create()
     * (App\Policies\ModulePolicy::create() needs 2 args and would
     * throw ArgumentCountError). Instance-level abilities below
     * (view/update/delete) pass a real object, which Gate does NOT
     * strip, so those work correctly through the policy.
     */
    public function index()
    {
        $publications = Publication::orderBy('name')->get();

        return view('media.publications.index', compact('publications'));
    }

    public function create()
    {
        $accounts = Account::query()->where('company_id', session('company_id'))->where('is_active', true)->where('account_type', 'Income')->orderBy('account_name')->get();

        return view('media.publications.create', compact('accounts'));
    }

    public function store(StorePublicationRequest $request)
    {
        $publication = Publication::create($request->validated());

        return redirect()->route('media.publications.show', $publication)
            ->with('success', 'Publication created!');
    }

    public function show(Publication $publication)
    {
        $this->authorize('view', $publication);

        return view('media.publications.show', compact('publication'));
    }

    public function edit(Publication $publication)
    {
        $this->authorize('update', $publication);

        $accounts = Account::query()->where('company_id', session('company_id'))->where('is_active', true)->where('account_type', 'Income')->orderBy('account_name')->get();

        return view('media.publications.edit', compact('publication', 'accounts'));
    }

    public function update(UpdatePublicationRequest $request, Publication $publication)
    {
        $this->authorize('update', $publication);

        $publication->update($request->validated());

        return redirect()->route('media.publications.show', $publication)
            ->with('success', 'Publication updated!');
    }

    public function destroy(Publication $publication)
    {
        $this->authorize('delete', $publication);

        $publication->delete();

        return redirect()->route('media.publications.index')
            ->with('success', 'Publication deleted!');
    }
}
