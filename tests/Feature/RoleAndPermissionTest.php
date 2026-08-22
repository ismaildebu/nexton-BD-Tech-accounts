<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed the complete role/permission matrix before each test.
    $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
});

it('seeds all expected roles', function () {
    expect(Role::pluck('name')->all())->toBe([
        'admin', 'manager', 'accountant', 'cashier',
        'sales', 'auditor', 'viewer',
    ]);
});

it('seeds the full permission list grouped by module', function () {
    expect(Permission::count())->toBeGreaterThanOrEqual(75);
    expect(Permission::whereNotNull('group')->count())->toBe(Permission::count());
});

it('gives admin every permission', function () {
    $admin = Role::findByName('admin');

    expect($admin->permissions->count())->toBe(Permission::count());
});

it('restricts cashier from payroll and financial reports', function () {
    $cashier = User::factory()->create(['role' => 'Accountant']);
    $cashier->assignRole('cashier');

    expect($cashier->can('vouchers.create'))->toBeTrue()
        ->and($cashier->can('salaries.create'))->toBeFalse()
        ->and($cashier->can('profit-loss.view'))->toBeFalse()
        ->and($cashier->can('invoices.create'))->toBeTrue()
        ->and($cashier->can('roles.manage'))->toBeFalse();
});

it('restricts sales from accounting posting actions', function () {
    $sales = User::factory()->create();
    $sales->assignRole('sales');

    expect($sales->can('sales-orders.create'))->toBeTrue()
        ->and($sales->can('vouchers.post'))->toBeFalse()
        ->and($sales->can('balance-sheet.view'))->toBeFalse();
});

it('lets an accountant view reports but not manage system users', function () {
    $accountant = User::factory()->create();
    $accountant->assignRole('accountant');

    expect($accountant->can('trial-balance.view'))->toBeTrue()
        ->and($accountant->can('balance-sheet.view'))->toBeTrue()
        ->and($accountant->can('users.create'))->toBeFalse()
        ->and($accountant->can('vouchers.post'))->toBeTrue();
});

it('blocks unauthorized users from the roles pages', function () {
    $viewer = User::factory()->create();
    $viewer->assignRole('viewer');

    $this->actingAs($viewer)
        ->get(route('system.roles.index'))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('error');
});

it('allows role managers to manage roles', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('system.roles.index'))
        ->assertOk();
});

it('creates a role with selected permissions', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('system.roles.store'), [
        'name' => 'branch_manager',
        'permissions' => ['vouchers.view', 'vouchers.create', 'invoices.view'],
    ]);

    $response->assertRedirect(route('system.roles.show', Role::findByName('branch_manager')));

    $role = Role::findByName('branch_manager');
    expect($role->permissions->pluck('name')->all())
        ->toBe(['vouchers.view', 'vouchers.create', 'invoices.view']);
});

it('protects seed roles from deletion', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->delete(route('system.roles.destroy', Role::findByName('admin')))
        ->assertRedirect(route('system.roles.index'))
        ->assertSessionHas('error');

    expect(Role::findByName('admin'))->not->toBeNull();
});

it('enforces permission middleware on protected routes', function () {
    $viewer = User::factory()->create();
    $viewer->assignRole('viewer');

    $this->actingAs($viewer)
        ->post(route('system.roles.store'), [
            'name' => 'rogue_role',
            'permissions' => ['roles.manage'],
        ])
        ->assertRedirect(route('dashboard'));

    expect(Role::where('name', 'rogue_role')->exists())->toBeFalse();
});
