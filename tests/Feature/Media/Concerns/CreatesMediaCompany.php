<?php

declare(strict_types=1);

namespace Tests\Feature\Media\Concerns;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

/**
 * Shared setup for Media module tests: a Media-business-type company
 * (so Company::hasModule('media') is true and the `module:media`
 * route middleware lets requests through) plus an admin user locked
 * to that company.
 */
trait CreatesMediaCompany
{
    protected function makeMediaCompany(array $attributes = []): Company
    {
        return Company::create([
            'company_name' => 'Test Media House',
            'business_type' => 'Media',
            'status' => true,
            ...$attributes,
        ]);
    }

    protected function makeMediaAdmin(?Company $company = null): User
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $company ??= $this->makeMediaCompany();

        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('admin');

        return $user;
    }
}
