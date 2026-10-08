<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Expense;
use App\Models\FinancialYear;
use App\Models\User;
use App\Services\SubscriptionService;
use Database\Seeders\PlanSeeder;

beforeEach(function () {
    $this->company = Company::factory()->create(['status' => true]);

    // Company admin: has every expenses.*, customers.*, vendors.*, balance-sheet.* permission.
    $this->admin = User::factory()->create(['company_id' => $this->company->id]);
    $this->admin->assignRole('admin');
});

// ---------------------------------------------------------------- customers / vendors

it('renders the bulk customer form', function () {
    $this->actingAs($this->admin)
        ->get(route('customers.bulk-create'))
        ->assertOk()
        ->assertSee('Bulk Add Customers');
});

it('renders the bulk vendor form', function () {
    $this->actingAs($this->admin)
        ->get(route('vendors.bulk-create'))
        ->assertOk();
});

// ------------------------------------------------------------------------ expenses

it('shows, edits and updates an expense', function () {
    $expense = Expense::create([
        'company_id' => $this->company->id,
        'expense_date' => '2026-10-01',
        'category' => 'Office Rent',
        'description' => 'October rent',
        'amount' => '15000.00',
        'status' => 'approved',
    ]);

    $this->actingAs($this->admin)
        ->get(route('expenses.show', $expense))
        ->assertOk()
        ->assertSee('Office Rent')
        ->assertSee('15,000.00');

    $this->actingAs($this->admin)
        ->get(route('expenses.edit', $expense))
        ->assertOk()
        ->assertSee('Office Rent');

    $this->actingAs($this->admin)
        ->put(route('expenses.update', $expense), [
            'expense_date' => '2026-10-01',
            'category' => 'Office Rent',
            'description' => 'October rent (revised)',
            'amount' => '16000.00',
        ])
        ->assertRedirect(route('expenses.index'));

    expect((string) $expense->refresh()->amount)->toBe('16000.00');
});

it('refuses a negative expense amount on update just like on create', function () {
    $expense = Expense::create([
        'company_id' => $this->company->id,
        'expense_date' => '2026-10-01',
        'category' => 'Office Rent',
        'amount' => '15000.00',
        'status' => 'approved',
    ]);

    $this->actingAs($this->admin)
        ->put(route('expenses.update', $expense), [
            'expense_date' => '2026-10-01',
            'category' => 'Office Rent',
            'amount' => '-5',
        ])
        ->assertSessionHasErrors('amount');

    expect((string) $expense->refresh()->amount)->toBe('15000.00');
});

it('does not show an expense of another company', function () {
    $other = Company::factory()->create(['status' => true]);
    $foreign = Expense::create([
        'company_id' => $other->id,
        'expense_date' => '2026-10-01',
        'category' => 'Secret',
        'amount' => '1.00',
        'status' => 'approved',
    ]);

    $this->actingAs($this->admin)
        ->get(route('expenses.show', $foreign))
        ->assertNotFound();

    $this->actingAs($this->admin)
        ->get(route('expenses.edit', $foreign))
        ->assertNotFound();
});

// ------------------------------------------------------------------- balance sheet

it('renders the printable balance sheet and opens the print dialog', function () {
    FinancialYear::create([
        'company_id' => $this->company->id,
        'year_name' => (string) now()->year,
        'start_date' => now()->startOfYear()->toDateString(),
        'end_date' => now()->endOfYear()->toDateString(),
        'is_active' => true,
        'is_closed' => false,
    ]);

    $this->actingAs($this->admin)
        ->get(route('balance-sheet.print'))
        ->assertOk()
        ->assertSee('Balance Sheet')
        ->assertSee('window.print()', false);
});

// ------------------------------------------------------------ admin: payments of a user

it('lists the subscriptions and payments of one user for the super admin', function () {
    $this->seed(PlanSeeder::class);

    $subject = User::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Subscribed Person',
    ]);
    app(SubscriptionService::class)->subscribeToFreePlan($subject);

    $superAdmin = User::factory()->create(['company_id' => null]);
    $superAdmin->assignRole('super-admin');

    $this->actingAs($superAdmin)
        ->get(route('admin.users.payments', $subject))
        ->assertOk()
        ->assertSee('Subscribed Person');
});

it('does not let a company admin open the per-user payment page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.users.payments', $this->admin))
        ->assertForbidden();
});
