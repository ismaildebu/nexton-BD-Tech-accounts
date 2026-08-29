<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Plan;
use App\Models\User;
use App\Services\SubscriptionService;
use Database\Seeders\PlanFeatureSeeder;
use Database\Seeders\PlanSeeder;

beforeEach(function () {
    $this->seed(PlanSeeder::class);
    $this->seed(PlanFeatureSeeder::class);
});

function makeOwnedCompany(string $businessType = 'Trading'): array
{
    $owner = User::factory()->create();
    $company = Company::create([
        'company_name' => 'Owned Co',
        'business_type' => $businessType,
        'owner_id' => $owner->id,
    ]);
    $owner->update(['company_id' => $company->id]);
    $owner->assignRole('admin');

    return [$company, $owner];
}

it('blocks adding a second user to a company once the Free plan user limit (1) is reached', function () {
    [$company, $owner] = makeOwnedCompany();
    app(SubscriptionService::class)->ensureHasSubscription($owner);

    // The owner itself already counts as the company's first user.
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');

    $response = $this->actingAs($superAdmin)->post('/system/users', [
        'name' => 'Second User',
        'email' => 'second@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'Accountant',
        'company_id' => $company->id,
    ]);

    $response->assertForbidden();
    expect(User::query()->where('email', 'second@example.com')->exists())->toBeFalse();
});

it('allows unlimited users on the Plus plan', function () {
    [$company, $owner] = makeOwnedCompany();
    $plus = Plan::query()->where('key', 'plus')->firstOrFail();
    app(SubscriptionService::class)->activatePlan($owner, $plus);

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');

    $response = $this->actingAs($superAdmin)->post('/system/users', [
        'name' => 'Second User',
        'email' => 'second@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'Accountant',
        'company_id' => $company->id,
    ]);

    $response->assertRedirect();
    expect(User::query()->where('email', 'second@example.com')->exists())->toBeTrue();
});

it('does not enforce the user limit for a legacy company with no owner_id', function () {
    $company = Company::create([
        'company_name' => 'Legacy Co',
        'business_type' => 'Trading',
        // owner_id intentionally omitted, simulating pre-existing data.
    ]);

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');

    $response = $this->actingAs($superAdmin)->post('/system/users', [
        'name' => 'Legacy User',
        'email' => 'legacy@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'Accountant',
        'company_id' => $company->id,
    ]);

    $response->assertRedirect();
    expect(User::query()->where('email', 'legacy@example.com')->exists())->toBeTrue();
});