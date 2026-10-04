<?php

use App\Models\Account;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

    $this->company = Company::factory()->create();
    $this->otherCompany = Company::factory()->create();

    session(['company_id' => $this->company->id]);
});

it('authorizes account actions according to permissions and company isolation', function () {
    $user = User::factory()->create();
    $user->assignRole('accountant');

    $account = Account::factory()->create([
        'company_id' => $this->company->id,
        'is_system' => false,
    ]);

    $otherCompanyAccount = Account::factory()->create([
        'company_id' => $this->otherCompany->id,
        'is_system' => false,
    ]);

    expect($user->can('view', $account))->toBeTrue()
        ->and($user->can('view', $otherCompanyAccount))->toBeFalse()
        ->and($user->can('update', $account))->toBeTrue()
        ->and($user->can('update', $otherCompanyAccount))->toBeFalse();
});
