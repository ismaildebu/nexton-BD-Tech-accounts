<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds roles, permissions, and the default role-permission matrix
 * for Nexton Accounts.
 *
 * Run:
 * php artisan db:seed --class=RoleAndPermissionSeeder
 *
 * Idempotent — safe to run multiple times.
 */
class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Permission name => group
     *
     * @var array<string, string>
     */
    private const PERMISSIONS = [

        // ---------------------------------------------------------
        // Accounting
        // ---------------------------------------------------------
        'accounts.view'           => 'accounting',
        'accounts.create'         => 'accounting',
        'accounts.edit'           => 'accounting',
        'accounts.delete'         => 'accounting',

        'vouchers.view'           => 'accounting',
        'vouchers.create'         => 'accounting',
        'vouchers.edit'           => 'accounting',
        'vouchers.submit'         => 'accounting',
        'vouchers.approve'        => 'accounting',
        'vouchers.post'           => 'accounting',
        'vouchers.cancel'         => 'accounting',
        'vouchers.print'          => 'accounting',
        'vouchers.delete'         => 'accounting',

        'ledger.view'             => 'accounting',
        'ledger.export'           => 'accounting',

        'trial-balance.view'      => 'accounting',
        'profit-loss.view'        => 'accounting',
        'balance-sheet.view'      => 'accounting',
        'cash-flow.view'          => 'accounting',

        'banking.view'            => 'accounting',
        'banking.create'          => 'accounting',
        'banking.edit'            => 'accounting',
        'banking.delete'         => 'accounting',

        'voucher-types.view'      => 'accounting',
        'voucher-types.manage'    => 'accounting',

        'financial-years.view'    => 'accounting',
        'financial-years.manage'  => 'accounting',

        // ---------------------------------------------------------
        // Sales
        // ---------------------------------------------------------
        'invoices.view'           => 'sales',
        'invoices.create'         => 'sales',
        'invoices.edit'           => 'sales',
        'invoices.delete'         => 'sales',

        'expenses.view'           => 'sales',
        'expenses.create'         => 'sales',
        'expenses.edit'           => 'sales',
        'expenses.delete'         => 'sales',

        'customers.view'          => 'sales',
        'customers.create'        => 'sales',
        'customers.edit'          => 'sales',
        'customers.delete'        => 'sales',

        'sales-orders.view'       => 'sales',
        'sales-orders.create'     => 'sales',
        'sales-orders.edit'       => 'sales',
        'sales-orders.delete'     => 'sales',

        // ---------------------------------------------------------
        // Procurement
        // ---------------------------------------------------------
        'vendors.view'            => 'procurement',
        'vendors.create'          => 'procurement',
        'vendors.edit'            => 'procurement',
        'vendors.delete'          => 'procurement',

        'purchase-orders.view'    => 'procurement',
        'purchase-orders.create'  => 'procurement',
        'purchase-orders.edit'    => 'procurement',
        'purchase-orders.delete'  => 'procurement',

        'purchase-bills.view'     => 'procurement',
        'purchase-bills.create'   => 'procurement',
        'purchase-bills.edit'     => 'procurement',
        'purchase-bills.delete'   => 'procurement',

        // ---------------------------------------------------------
        // Inventory
        // ---------------------------------------------------------
        'products.view'           => 'inventory',
        'products.create'         => 'inventory',
        'products.edit'           => 'inventory',
        'products.delete'         => 'inventory',

        'stock.view'              => 'inventory',
        'stock.move'              => 'inventory',
        'stock.transfer'          => 'inventory',

        'warehouses.view'         => 'inventory',
        'warehouses.manage'       => 'inventory',

        // ---------------------------------------------------------
        // HR
        // ---------------------------------------------------------
        'employees.view'          => 'hr',
        'employees.create'        => 'hr',
        'employees.edit'          => 'hr',
        'employees.delete'        => 'hr',

        'salaries.view'           => 'hr',
        'salaries.create'         => 'hr',
        'salaries.edit'           => 'hr',
        'salaries.delete'         => 'hr',

        // ---------------------------------------------------------
        // Company & Documents
        // ---------------------------------------------------------
        'companies.view'          => 'company',
        'companies.create'        => 'company',
        'companies.edit'          => 'company',
        'companies.delete'        => 'company',

        'legal-documents.view'    => 'company',
        'legal-documents.manage'  => 'company',

        'settings.view'           => 'company',
        'settings.manage'         => 'company',

        // ---------------------------------------------------------
        // System
        // ---------------------------------------------------------
        'users.view'              => 'system',
        'users.create'            => 'system',
        'users.edit'              => 'system',
        'users.delete'            => 'system',
        'users.manage'            => 'system',

        'roles.view'              => 'system',
        'roles.manage'            => 'system',

        'permissions.view'        => 'system',
        'permissions.manage'     => 'system',

        'dashboard.view'          => 'system',

        // ---------------------------------------------------------
        // Media Business Module
        // ---------------------------------------------------------
        'media-publications.view'    => 'media',
        'media-publications.create'  => 'media',
        'media-publications.edit'    => 'media',
        'media-publications.delete'  => 'media',

        'media-parties.view'         => 'media',
        'media-parties.create'       => 'media',
        'media-parties.edit'         => 'media',
        'media-parties.delete'       => 'media',

        'media-print-planning.view'    => 'media',
        'media-print-planning.create'  => 'media',
        'media-print-planning.approve' => 'media',

        'media-print-orders.view'    => 'media',
        'media-print-orders.create'  => 'media',
        'media-print-orders.edit'    => 'media',
        'media-print-orders.approve' => 'media',
        'media-print-orders.print'   => 'media',

        'media-distributions.view'   => 'media',
        'media-distributions.create' => 'media',
        'media-distributions.print'  => 'media',

        'media-returns.view'         => 'media',
        'media-returns.create'       => 'media',

        'media-collections.view'     => 'media',
        'media-collections.create'   => 'media',
    ];

    /**
     * Role => list of permission names
     *
     * super-admin gets every permission.
     *
     * @var array<string, array<int, string>>
     */
    private const MATRIX = [

        // =========================================================
        // SUPER ADMIN
        // =========================================================
        'super-admin' => [],

        // =========================================================
        // COMPANY ADMIN
        // =========================================================
        'admin' => [

            // Accounting
            'accounts.view',
            'accounts.create',
            'accounts.edit',
            'accounts.delete',

            'vouchers.view',
            'vouchers.create',
            'vouchers.edit',
            'vouchers.submit',
            'vouchers.approve',
            'vouchers.post',
            'vouchers.cancel',
            'vouchers.print',
            'vouchers.delete',

            'ledger.view',
            'ledger.export',

            'trial-balance.view',
            'profit-loss.view',
            'balance-sheet.view',
            'cash-flow.view',

            'banking.view',
            'banking.create',
            'banking.edit',
            'banking.delete',

            'voucher-types.view',
            'voucher-types.manage',

            'financial-years.view',
            'financial-years.manage',

            // Sales
            'invoices.view',
            'invoices.create',
            'invoices.edit',
            'invoices.delete',

            'expenses.view',
            'expenses.create',
            'expenses.edit',
            'expenses.delete',

            'customers.view',
            'customers.create',
            'customers.edit',
            'customers.delete',

            'sales-orders.view',
            'sales-orders.create',
            'sales-orders.edit',
            'sales-orders.delete',

            // Procurement
            'vendors.view',
            'vendors.create',
            'vendors.edit',
            'vendors.delete',

            'purchase-orders.view',
            'purchase-orders.create',
            'purchase-orders.edit',
            'purchase-orders.delete',

            'purchase-bills.view',
            'purchase-bills.create',
            'purchase-bills.edit',
            'purchase-bills.delete',

            // Inventory
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',

            'stock.view',
            'stock.move',
            'stock.transfer',

            'warehouses.view',
            'warehouses.manage',

            // HR
            'employees.view',
            'employees.create',
            'employees.edit',
            'employees.delete',

            'salaries.view',
            'salaries.create',
            'salaries.edit',
            'salaries.delete',

            // Company — Admin can only view/edit their OWN company
            // (enforced in CompanyController, not just by this permission).
            // create/delete are Super Admin only, so they are deliberately
            // left out of this list.
            'companies.view',
            'companies.edit',

            'legal-documents.view',
            'legal-documents.manage',

            'settings.view',
            'settings.manage',

            'dashboard.view',

            // System — Admin can create/manage users, but only within
            // their own company (enforced in UserController, not here).
            // roles.manage / permissions.manage stay Super Admin only,
            // so Admin cannot grant itself or anyone the super-admin role.
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.manage',

            // Media Business Module
            'media-publications.view',
            'media-publications.create',
            'media-publications.edit',
            'media-publications.delete',

            'media-parties.view',
            'media-parties.create',
            'media-parties.edit',
            'media-parties.delete',

            'media-print-planning.view',
            'media-print-planning.create',
            'media-print-planning.approve',

            'media-print-orders.view',
            'media-print-orders.create',
            'media-print-orders.edit',
            'media-print-orders.approve',
            'media-print-orders.print',

            'media-distributions.view',
            'media-distributions.create',
            'media-distributions.print',

            'media-returns.view',
            'media-returns.create',

            'media-collections.view',
            'media-collections.create',
        ],

        // =========================================================
        // ACCOUNTANT
        // =========================================================
        'accountant' => [

            'accounts.view',
            'accounts.create',
            'accounts.edit',

            'vouchers.view',
            'vouchers.create',
            'vouchers.edit',
            'vouchers.submit',
            'vouchers.print',

            'ledger.view',
            'ledger.export',

            'trial-balance.view',
            'profit-loss.view',
            'balance-sheet.view',
            'cash-flow.view',

            'banking.view',
            'banking.create',
            'banking.edit',

            'voucher-types.view',
            'financial-years.view',

            'invoices.view',
            'invoices.create',
            'invoices.edit',

            'expenses.view',
            'expenses.create',
            'expenses.edit',

            'customers.view',
            'vendors.view',

            'purchase-orders.view',
            'purchase-bills.view',

            'products.view',
            'stock.view',

            'companies.view',
            'legal-documents.view',

            'dashboard.view',
        ],

        // =========================================================
        // CASHIER / PETTY CASH
        // =========================================================
        'cashier' => [

            'accounts.view',

            'vouchers.view',
            'vouchers.create',
            'vouchers.print',

            'invoices.view',
            'invoices.create',

            'expenses.view',
            'expenses.create',

            'customers.view',

            'products.view',
            'stock.view',
            'stock.move',

            'dashboard.view',
        ],

        // =========================================================
        // SALES
        // =========================================================
        'sales' => [

            'invoices.view',
            'invoices.create',
            'invoices.edit',

            'expenses.view',
            'expenses.create',

            'customers.view',
            'customers.create',
            'customers.edit',

            'sales-orders.view',
            'sales-orders.create',
            'sales-orders.edit',

            'vendors.view',
            'purchase-orders.view',
            'purchase-bills.view',

            'products.view',
            'stock.view',

            'dashboard.view',
        ],

        // =========================================================
        // AUDITOR
        // =========================================================
        'auditor' => [

            'accounts.view',

            'vouchers.view',
            'vouchers.print',

            'ledger.view',
            'ledger.export',

            'trial-balance.view',
            'profit-loss.view',
            'balance-sheet.view',
            'cash-flow.view',

            'invoices.view',
            'expenses.view',

            'customers.view',
            'vendors.view',

            'purchase-orders.view',
            'purchase-bills.view',

            'sales-orders.view',

            'products.view',
            'stock.view',

            'companies.view',
            'legal-documents.view',

            'dashboard.view',
        ],

        // =========================================================
        // VIEWER
        // =========================================================
        'viewer' => [

            'accounts.view',

            'vouchers.view',

            'ledger.view',

            'trial-balance.view',
            'profit-loss.view',
            'balance-sheet.view',
            'cash-flow.view',

            'invoices.view',
            'expenses.view',

            'customers.view',
            'vendors.view',

            'purchase-orders.view',
            'purchase-bills.view',

            'sales-orders.view',

            'products.view',
            'stock.view',

            'dashboard.view',
        ],
    ];

    public function run(): void
    {
        // Reset Spatie permission cache.
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // =========================================================
        // 1. Create / sync roles
        // =========================================================
        foreach (array_keys(self::MATRIX) as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }

        // =========================================================
        // 2. Create / sync permissions
        // =========================================================
        foreach (self::PERMISSIONS as $name => $group) {
            Permission::updateOrCreate(
                [
                    'name' => $name,
                    'guard_name' => 'web',
                ],
                [
                    'group' => $group,
                ],
            );
        }

        // =========================================================
        // 3. Attach permissions to roles
        // =========================================================
        $allPermissions = Permission::all();

        foreach (self::MATRIX as $roleName => $permissionNames) {

            $role = Role::findByName($roleName, 'web');

            // Super Admin gets every permission.
            $names = $roleName === 'super-admin'
                ? $allPermissions->pluck('name')->all()
                : $permissionNames;

            $role->syncPermissions(
                $allPermissions
                    ->whereIn('name', $names)
                    ->pluck('id')
                    ->all(),
            );
        }

        // =========================================================
        // 4. Existing Admin user becomes Super Admin
        // =========================================================
        $firstAdmin = User::where('role', 'Admin')
            ->where('status', true)
            ->first();

        if ($firstAdmin !== null) {
            $firstAdmin->syncRoles(['super-admin']);
        }

        // =========================================================
        // 5. Clear permission cache again
        // =========================================================
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}