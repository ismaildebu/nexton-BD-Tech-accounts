<?php

declare(strict_types=1);

use App\Http\Middleware\SetCompanyContext;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createSuperAdminForCompanySwitchTest(): User
{
    $user = User::factory()->create([
        'company_id' => null,
        'status' => true,
    ]);

    $user->syncRoles(['super-admin']);

    return $user;
}

function createCompanyUserForCompanySwitchTest(Company $company): User
{
    $user = User::factory()->create([
        'company_id' => $company->id,
        'status' => true,
    ]);

    $user->syncRoles(['admin']);

    return $user;
}

function createFinancialYearForCompanySwitchTest(Company $company): FinancialYear
{
    return FinancialYear::create([
        'company_id' => $company->id,
        'year_name' => 'FY 2026-2027',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'is_active' => true,
    ]);
}

it('allows a super admin to switch to an active company', function (): void {
    $user = createSuperAdminForCompanySwitchTest();

    $company = Company::factory()->create([
        'status' => true,
    ]);

    $financialYear = createFinancialYearForCompanySwitchTest($company);

    $response = $this
        ->actingAs($user)
        ->from(route('companies.index'))
        ->post(route('switch.company'), [
            'company_id' => $company->id,
        ]);

    $response
        ->assertRedirect(route('companies.index'))
        ->assertSessionHas('success', 'Company switched successfully.');

    $this->assertSame(
        $company->id,
        session('company_id')
    );

    $this->assertSame(
        $company->company_name,
        session('company_name')
    );

    $this->assertSame(
        $financialYear->id,
        session('financial_year_id')
    );
});

it('allows a super admin to switch between active companies', function (): void {
    $user = createSuperAdminForCompanySwitchTest();

    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $this
        ->actingAs($user)
        ->post(route('switch.company'), [
            'company_id' => $companyA->id,
        ])
        ->assertSessionHas('company_id', $companyA->id);

    $this
        ->actingAs($user)
        ->post(route('switch.company'), [
            'company_id' => $companyB->id,
        ])
        ->assertSessionHas('company_id', $companyB->id);

    expect(session('company_id'))
        ->toBe($companyB->id);
});

it('prevents a company scoped user from switching to another company', function (): void {
    $ownCompany = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $user = createCompanyUserForCompanySwitchTest($ownCompany);

    $response = $this
        ->actingAs($user)
        ->post(route('switch.company'), [
            'company_id' => $otherCompany->id,
        ]);

    $response->assertForbidden();

    expect(session('company_id'))
        ->not->toBe($otherCompany->id);
});

it('allows a company scoped user to switch to their own company', function (): void {
    $company = Company::factory()->create();

    $user = createCompanyUserForCompanySwitchTest($company);

    $response = $this
        ->actingAs($user)
        ->post(route('switch.company'), [
            'company_id' => $company->id,
        ]);

    $response
        ->assertRedirect()
        ->assertSessionHas('company_id', $company->id);
});

it('rejects switching to an inactive company', function (): void {
    $user = createSuperAdminForCompanySwitchTest();

    $company = Company::factory()
        ->inactive()
        ->create();

    $response = $this
        ->actingAs($user)
        ->post(route('switch.company'), [
            'company_id' => $company->id,
        ]);

    $response->assertNotFound();

    expect(session('company_id'))
        ->not->toBe($company->id);
});

it('rejects switching to a non existing company', function (): void {
    $user = createSuperAdminForCompanySwitchTest();

    $response = $this
        ->actingAs($user)
        ->post(route('switch.company'), [
            'company_id' => 999999,
        ]);

    $response->assertSessionHasErrors('company_id');
});

it('requires authentication for company switching', function (): void {
    $company = Company::factory()->create();

    $response = $this->post(route('switch.company'), [
        'company_id' => $company->id,
    ]);

    $response->assertRedirect(route('login'));
});

it('sets the selected active company as the application current company context', function (): void {
    $user = createSuperAdminForCompanySwitchTest();

    $company = Company::factory()->create();

    $this
        ->actingAs($user)
        ->post(route('switch.company'), [
            'company_id' => $company->id,
        ]);

    $this
        ->actingAs($user)
        ->get(route('companies.index'));

    expect(app()->bound('currentCompany'))
        ->toBeTrue();

    expect(app('currentCompany')->id)
        ->toBe($company->id);
});