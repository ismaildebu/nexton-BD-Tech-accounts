<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Publication;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

/**
 * Regression test for a middleware-ordering bug found while verifying
 * the Media module: Laravel's core SubstituteBindings middleware (which
 * resolves {publication}, {customer}, {vendor}, etc. from the URL) has
 * a fixed high priority and, by default, runs BEFORE any custom
 * middleware not in that priority list — including EnsureCompanySelected
 * and SetActiveCompany, both of which are responsible for locking
 * session('company_id') to the current user's own company.
 *
 * That meant a user's very FIRST request in a session (before either
 * of those had ever run for them) could have route-model-binding
 * resolve against no company scope at all, or against a stale/wrong
 * company_id, letting them load another company's record by id.
 *
 * Fixed in bootstrap/app.php via prependToPriorityList(), and in
 * SetActiveCompany (which used to default to Company::first() instead
 * of the user's own company). This test deliberately does NOT make any
 * "warm-up" request first — it must pass on a user's very first hit.
 */
it('blocks cross-company route-model-binding on a user\'s very first request in a fresh session', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $companyA = Company::create([
        'company_name' => 'Company A', 'business_type' => 'Media', 'status' => true,
    ]);
    $companyB = Company::create([
        'company_name' => 'Company B', 'business_type' => 'Media', 'status' => true,
    ]);

    // Company A is created FIRST, so Company::first() (the old,
    // buggy fallback) would resolve to Company A — the exact
    // scenario that made the bug possible.
    session(['company_id' => $companyA->id]);
    $publicationInA = Publication::create(['name' => 'A Times', 'code' => 'AT', 'selling_price' => 10]);

    $userB = User::factory()->create(['company_id' => $companyB->id]);
    $userB->assignRole('admin');

    // Fresh session for this "request" — no prior warm-up call. This
    // is the exact case the old middleware order got wrong.
    $this->flushSession();

    $this->actingAs($userB)
        ->get(route('media.publications.show', $publicationInA))
        ->assertNotFound();
});

it('never lets SetActiveCompany hand a company-scoped user a company that is not their own', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $companyA = Company::create([
        'company_name' => 'Company A', 'business_type' => 'Media', 'status' => true,
    ]);
    $companyB = Company::create([
        'company_name' => 'Company B', 'business_type' => 'Media', 'status' => true,
    ]);

    $userB = User::factory()->create(['company_id' => $companyB->id]);
    $userB->assignRole('admin');

    $this->flushSession();

    $this->actingAs($userB)->get(route('media.publications.index'));

    expect(session('company_id'))->toBe($companyB->id);
});
