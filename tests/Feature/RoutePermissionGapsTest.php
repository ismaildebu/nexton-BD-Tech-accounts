<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Employee;
use App\Models\User;

beforeEach(function () {
    $this->company = Company::factory()->create(['status' => true]);

    // A company user without any role and without any permission.
    $this->plain = User::factory()->create(['company_id' => $this->company->id]);

    $this->employee = Employee::create([
        'company_id' => $this->company->id,
        'name' => 'Test Employee',
    ]);
});

it('forbids bulk customer creation without customers.create', function () {
    $this->actingAs($this->plain)
        ->get(route('customers.bulk-create'))
        ->assertForbidden();

    $this->actingAs($this->plain)
        ->post(route('customers.bulk-store'), [
            'customers' => [['name' => 'Rogue Customer']],
        ])
        ->assertForbidden();

    expect(Customer::withoutGlobalScopes()->count())->toBe(0);
});

it('lets a user with customers.create through the bulk customer gate', function () {
    $this->plain->givePermissionTo('customers.create');

    // An empty list fails validation, which proves the permission gate was passed.
    $this->actingAs($this->plain)
        ->post(route('customers.bulk-store'), ['customers' => []])
        ->assertSessionHasErrors('customers');
});

it('forbids bulk vendor creation without vendors.create', function () {
    $this->actingAs($this->plain)
        ->get(route('vendors.bulk-create'))
        ->assertForbidden();

    $this->actingAs($this->plain)
        ->post(route('vendors.bulk-store'), [
            'vendors' => [['name' => 'Rogue Vendor']],
        ])
        ->assertForbidden();
});

it('lets a user with vendors.create through the bulk vendor gate', function () {
    $this->plain->givePermissionTo('vendors.create');

    $this->actingAs($this->plain)
        ->post(route('vendors.bulk-store'), ['vendors' => []])
        ->assertSessionHasErrors('vendors');
});

it('forbids an employee photo without employees.view', function () {
    $this->actingAs($this->plain)
        ->get(route('employees.photo', $this->employee))
        ->assertForbidden();
});

it('lets a user with employees.view reach the employee photo check', function () {
    $this->plain->givePermissionTo('employees.view');

    // The employee has no photo, so the controller answers 404 (not 403).
    $this->actingAs($this->plain)
        ->get(route('employees.photo', $this->employee))
        ->assertNotFound();
});

it('renders the admin customer payment screens (they extended a layout that does not exist)', function () {
    $admin = User::factory()->create(['company_id' => $this->company->id]);
    $admin->assignRole('admin');

    $customer = Customer::query()->create([
        'company_id' => $this->company->id,
        'customer_code' => 'SA-VIEW-1',
        'name' => 'View Customer',
        'phone' => '01700000000',
        'email' => 'view@example.com',
        'customer_type' => 'Individual',
        'credit_limit' => 0,
        'opening_balance' => 0,
        'balance_type' => 'Receivable',
        'is_active' => true,
    ]);

    $payment = CustomerPayment::query()->create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'reference_id' => $customer->customer_code,
        'amount' => '500.00',
        'payment_date' => now()->toDateString(),
        'payment_method' => 'bank_transfer',
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->get(route('customer-payment.admin.list'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('customer-payment.admin.show', $payment))
        ->assertOk();
});
