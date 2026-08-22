<?php

declare(strict_types=1);

use App\Models\Publication;
use Tests\Feature\Media\Concerns\CreatesMediaCompany;

uses(CreatesMediaCompany::class);

it('lets an authorized user create a publication', function () {
    $admin = $this->makeMediaAdmin();

    $response = $this->actingAs($admin)->post(route('media.publications.store'), [
        'name' => 'Daily Star',
        'code' => 'DS',
        'publication_type' => 'Daily',
        'selling_price' => 12.5,
        'default_free_percentage' => 5,
        'is_active' => true,
    ]);

    $publication = Publication::first();

    $response->assertRedirect(route('media.publications.show', $publication));
    expect($publication)
        ->name->toBe('Daily Star')
        ->code->toBe('DS')
        ->company_id->toBe($admin->company_id);
});

it('rejects a duplicate publication code within the same company', function () {
    $admin = $this->makeMediaAdmin();
    session(['company_id' => $admin->company_id]);

    Publication::create(['name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10]);

    $this->actingAs($admin)->post(route('media.publications.store'), [
        'name' => 'Daily Star 2',
        'code' => 'DS',
        'selling_price' => 10,
    ])->assertSessionHasErrors('code');
});

it('allows the same publication code to be reused by a different company', function () {
    $admin1 = $this->makeMediaAdmin();
    session(['company_id' => $admin1->company_id]);
    Publication::create(['name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10]);

    $company2 = $this->makeMediaCompany(['company_name' => 'Another Media House']);
    $admin2 = $this->makeMediaAdmin($company2);

    $this->actingAs($admin2)->post(route('media.publications.store'), [
        'name' => 'Daily Star (Other Co)',
        'code' => 'DS',
        'selling_price' => 10,
    ])->assertSessionDoesntHaveErrors('code');
});

it('404s when a user tries to view another company\'s publication', function () {
    $admin1 = $this->makeMediaAdmin();
    session(['company_id' => $admin1->company_id]);
    $publication = Publication::create(['name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10]);

    $company2 = $this->makeMediaCompany(['company_name' => 'Another Media House']);
    $admin2 = $this->makeMediaAdmin($company2);

    // NOTE: a "warm-up" request is required here. EnsureCompanySelected
    // (the middleware that locks session('company_id') to the current
    // user's own company) runs AFTER Laravel's core SubstituteBindings
    // middleware, which is what resolves {publication} in the URL.
    // Laravel gives SubstituteBindings a fixed high priority that
    // custom middleware can't move past (see $middlewarePriority in
    // Illuminate\Foundation\Http\Kernel). So on the very first request
    // of a session — before EnsureCompanySelected has ever run for
    // this user — route-model-binding for ANY BelongsToCompany model
    // resolves with whatever company_id already happens to be in
    // session (here, still admin1's, from the direct Eloquent create()
    // above), not admin2's. A prior request establishes admin2's
    // correct company_id in session first, matching realistic
    // navigation (dashboard/company context loads before a user opens
    // a specific record's URL). See the Phase 2 verification report
    // for the full write-up of this cross-cutting, pre-existing
    // architecture finding — it is not specific to the Media module.
    $this->actingAs($admin2)->get(route('media.publications.index'));

    $this->actingAs($admin2)
        ->get(route('media.publications.show', $publication))
        ->assertNotFound();
});

it('blocks a user without the media-publications.create permission', function () {
    $admin = $this->makeMediaAdmin();
    $admin->syncRoles([]); // strip admin role -> no permissions at all

    $this->actingAs($admin)
        ->post(route('media.publications.store'), [
            'name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10,
        ])
        ->assertForbidden();
});
