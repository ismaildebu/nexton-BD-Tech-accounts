<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * BelongsToCompany
 * -----------------
 * Add this trait to any model that has a `company_id` column and
 * must never be visible/editable outside the currently selected
 * company (session('company_id')).
 *
 * What it fixes:
 *   Previously, controllers like CustomerController, VendorController,
 *   EmployeeController, etc. used Laravel's implicit route-model
 *   binding (e.g. `edit(Customer $customer)`) with NO company check.
 *   Any logged-in user could view/edit/delete another company's
 *   record just by changing the ID in the URL (IDOR).
 *
 * How it fixes it:
 *   A global scope is added to every query built for the model,
 *   including the query Laravel's route-model binding uses under
 *   the hood. So `Route::resource('customers', ...)` calling
 *   `edit(Customer $customer)` will now throw a 404 automatically
 *   if that customer belongs to a different company — no manual
 *   `authorizeCompany()` check needed in each controller anymore.
 *
 * It also auto-fills `company_id` on create() if the attribute
 * was left empty, so `Model::create([...])` calls that forgot to
 * pass company_id won't silently insert a NULL/wrong company.
 *
 * NOTE: only apply this to models whose table has a `company_id`
 * column. For models without one (e.g. StockTransfer, which is
 * scoped indirectly through its Product/Warehouse), scope manually
 * in the controller instead.
 */
trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder): void {
            if (session()->has('company_id')) {
                $builder->where(
                    $builder->getModel()->getTable() . '.company_id',
                    session('company_id')
                );
            }
        });

        static::creating(function (Model $model): void {
            if (empty($model->company_id) && session()->has('company_id')) {
                $model->company_id = session('company_id');
            }
        });
    }

    /**
     * Explicitly bypass the company scope when a cross-company query
     * is genuinely needed (e.g. a super-admin report). Use sparingly
     * and only after checking real permissions in the controller.
     */
    public function scopeWithoutCompanyScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('company');
    }
}