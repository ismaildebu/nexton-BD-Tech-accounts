<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
     * is genuinely needed and properly authorized.
     */
    public function scopeWithoutCompanyScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('company');
    }
}