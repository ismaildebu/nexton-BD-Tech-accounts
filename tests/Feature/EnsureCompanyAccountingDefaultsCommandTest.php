<?php

use App\Models\Account;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function hasPurchaseClearing(int $companyId): bool
{
    return Account::withoutGlobalScopes()
        ->where('company_id', $companyId)
        ->where('account_name', 'Purchase Clearing')
        ->where('account_type', 'Liability')
        ->where('is_active', true)
        ->exists();
}

it('creates the missing defaults for every existing company', function () {
    $a = Company::create(['company_name' => 'Old Company A', 'business_type' => 'Trading']);
    $b = Company::create(['company_name' => 'Old Company B', 'business_type' => 'Trading']);

    expect(hasPurchaseClearing($a->id))->toBeFalse();

    $this->artisan('accounting:ensure-defaults')->assertSuccessful();

    expect(hasPurchaseClearing($a->id))->toBeTrue()
        ->and(hasPurchaseClearing($b->id))->toBeTrue();
});

it('is idempotent', function () {
    $company = Company::create(['company_name' => 'Old Company', 'business_type' => 'Trading']);

    $this->artisan('accounting:ensure-defaults')->assertSuccessful();
    $count = Account::withoutGlobalScopes()->where('company_id', $company->id)->count();

    $this->artisan('accounting:ensure-defaults')->assertSuccessful();

    expect(Account::withoutGlobalScopes()->where('company_id', $company->id)->count())
        ->toBe($count);
});

it('can be limited to a single company', function () {
    $a = Company::create(['company_name' => 'Company A', 'business_type' => 'Trading']);
    $b = Company::create(['company_name' => 'Company B', 'business_type' => 'Trading']);

    $this->artisan('accounting:ensure-defaults', ['--company' => $a->id])->assertSuccessful();

    expect(hasPurchaseClearing($a->id))->toBeTrue()
        ->and(hasPurchaseClearing($b->id))->toBeFalse();
});

it('fails when the company does not exist', function () {
    $this->artisan('accounting:ensure-defaults', ['--company' => 999999])->assertFailed();
});
