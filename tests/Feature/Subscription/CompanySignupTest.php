<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\AccountTemplateSeeder;
use Database\Seeders\PlanFeatureSeeder;
use Database\Seeders\PlanSeeder;

beforeEach(function () {
    $this->seed(PlanSeeder::class);
    $this->seed(PlanFeatureSeeder::class);
    $this->seed(AccountTemplateSeeder::class);
});

it('lets a guest create a company, an admin account, and a Free subscription in one request', function () {
    $response = $this->post('/company/register', [
        'company_name' => 'Acme Traders',
        'business_type' => 'Trading',
        'admin_name' => 'Jane Admin',
        'admin_email' => 'jane@example.com',
        'admin_password' => 'password',
        'admin_password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('dashboard.index'));

    $company = Company::query()->where('company_name', 'Acme Traders')->firstOrFail();
    $admin = User::query()->where('email', 'jane@example.com')->firstOrFail();

    expect($admin->company_id)->toBe($company->id)
        ->and($admin->hasRole('admin'))->toBeTrue()
        ->and($company->owner_id)->toBe($admin->id)
        ->and($admin->activeSubscription)->not->toBeNull()
        ->and($admin->activeSubscription->plan->key)->toBe('free')
        ->and($admin->activeSubscription->status)->toBe(Subscription::STATUS_ACTIVE);

    $this->assertAuthenticatedAs($admin);
});

it('rejects self-signup with a duplicate admin email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->post('/company/register', [
        'company_name' => 'Acme Traders',
        'business_type' => 'Trading',
        'admin_name' => 'Jane Admin',
        'admin_email' => 'taken@example.com',
        'admin_password' => 'password',
        'admin_password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('admin_email');
    expect(Company::query()->where('company_name', 'Acme Traders')->exists())->toBeFalse();
});

it('rejects self-signup when the password confirmation does not match', function () {
    $response = $this->post('/company/register', [
        'company_name' => 'Acme Traders',
        'business_type' => 'Trading',
        'admin_name' => 'Jane Admin',
        'admin_email' => 'jane@example.com',
        'admin_password' => 'password',
        'admin_password_confirmation' => 'different',
    ]);

    $response->assertSessionHasErrors('admin_password');
    expect(User::query()->where('email', 'jane@example.com')->exists())->toBeFalse();
});

it('does not create a company or user when self-signup fails validation (no partial rows)', function () {
    $this->post('/company/register', [
        'company_name' => '',
        'business_type' => 'Trading',
        'admin_name' => 'Jane Admin',
        'admin_email' => 'jane@example.com',
        'admin_password' => 'password',
        'admin_password_confirmation' => 'password',
    ]);

    expect(Company::query()->count())->toBe(0)
        ->and(User::query()->where('email', 'jane@example.com')->exists())->toBeFalse();
});