<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * General-purpose module policy.
 *
 * Maps resource-level abilities (viewAny, view, create, update, delete,
 * post, cancel, print, export) to granular permissions using the
 * `{module}.{action}` convention. The `{module}` key is inferred from the
 * model type (e.g. Invoice → invoices, PurchaseBill → purchase-bills).
 *
 * Register in AppServiceProvider:
 *   Gate::policy(Invoice::class, ModulePolicy::class);
 */
class ModulePolicy
{
    /**
     * Module key per model class.
     *
     * @var array<class-string, string>
     */
    private const MODEL_MODULE_MAP = [
        \App\Models\Account::class         => 'accounts',
        \App\Models\Transaction::class     => 'vouchers',
        \App\Models\Invoice::class         => 'invoices',
        \App\Models\Expense::class         => 'expenses',
        \App\Models\BankAccount::class     => 'banking',
        \App\Models\Customer::class        => 'customers',
        \App\Models\Vendor::class          => 'vendors',
        \App\Models\SalesOrder::class      => 'sales-orders',
        \App\Models\PurchaseOrder::class   => 'purchase-orders',
        \App\Models\PurchaseBill::class    => 'purchase-bills',
        \App\Models\Product::class         => 'products',
        \App\Models\Warehouse::class       => 'warehouses',
        \App\Models\StockMovement::class   => 'stock',
        \App\Models\StockTransfer::class   => 'stock',
        \App\Models\Employee::class        => 'employees',
        \App\Models\Salary::class          => 'salaries',
        \App\Models\Company::class         => 'companies',
        \App\Models\FinancialYear::class   => 'financial-years',
        \App\Models\VoucherType::class     => 'voucher-types',
        \App\Models\LegalDocument::class   => 'legal-documents',
        \App\Models\User::class            => 'users',
        \App\Models\Role::class            => 'roles',
        \App\Models\Permission::class      => 'permissions',

        \App\Models\Publication::class     => 'media-publications',
        \App\Models\MediaParty::class      => 'media-parties',
        \App\Models\PrintPlan::class       => 'media-print-planning',
        \App\Models\PrintOrder::class      => 'media-print-orders',
        \App\Models\MediaDistribution::class => 'media-distributions',
        \App\Models\MediaReturn::class     => 'media-returns',
        \App\Models\MediaCollection::class => 'media-collections',
    ];

    /**
     * NOTE ON viewAny()/create() BELOW: Laravel's Gate strips a leading
     * *string* argument before invoking a policy method — it assumes a
     * bare class-string passed to authorize()/can() was only there to
     * pick which policy applies, and is not meant to reach the method
     * itself (see Illuminate\Auth\Access\Gate::callPolicyMethod()).
     * That means $this->authorize('create', SomeModel::class) can
     * NEVER actually deliver $modelClass to these two methods — Gate
     * calls them as create($user) / viewAny($user) with zero extra
     * args, which throws ArgumentCountError against the signatures
     * below. This was true before Media routes existed too; it just
     * was never exercised because no model was registered against
     * this policy via Gate::policy() until the Media module needed it.
     * Callers must authorize the underlying permission directly for
     * class-level checks instead — e.g. $user->can('publications.create')
     * or the 'can-permission:<name>' route middleware — and reserve
     * $this->authorize() on this policy for instance-level abilities
     * (view/update/delete/approve/...) below, which pass a real object
     * and are NOT stripped.
     */
    public function viewAny(User $user, string $modelClass): bool
    {
        return $user->can($this->module($modelClass) . '.view');
    }

    public function view(User $user, object $model): bool
    {
        return $user->can($this->moduleFor($model) . '.view');
    }

    public function create(User $user, string $modelClass): bool
    {
        return $user->can($this->module($modelClass) . '.create');
    }

    public function update(User $user, object $model): bool
    {
        return $user->can($this->moduleFor($model) . '.edit');
    }

    public function delete(User $user, object $model): bool
    {
        return $user->can($this->moduleFor($model) . '.delete');
    }

    /**
     * Voucher-specific abilities.
     */
    public function post(User $user, object $model): bool
    {
        return $user->can($this->moduleFor($model) . '.post');
    }

    public function cancel(User $user, object $model): bool
    {
        return $user->can($this->moduleFor($model) . '.cancel');
    }

    /**
     * Approval workflow abilities (Print Plan approve/reject,
     * Print Order approve/status-transition).
     */
    public function approve(User $user, object $model): bool
    {
        return $user->can($this->moduleFor($model) . '.approve');
    }

    public function updateStatus(User $user, object $model): bool
    {
        return $user->can($this->moduleFor($model) . '.approve');
    }

    public function print(User $user, object $model): bool
    {
        return $user->can($this->moduleFor($model) . '.print');
    }

    public function export(User $user, object $model): bool
    {
        return $user->can($this->moduleFor($model) . '.export');
    }

    private function moduleFor(object $model): string
    {
        return $this->module(get_class($model));
    }

    private function module(string $modelClass): string
    {
        if (!isset(self::MODEL_MODULE_MAP[$modelClass])) {
            abort(403, 'No module permission defined for this model.');
        }

        return self::MODEL_MODULE_MAP[$modelClass];
    }
}
