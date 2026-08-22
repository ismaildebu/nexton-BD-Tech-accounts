<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Tests\Feature\Media\Concerns\CreatesMediaCompany;

uses(CreatesMediaCompany::class);

it('lets a Media-business-type company reach media routes', function () {
    $admin = $this->makeMediaAdmin();

    $this->actingAs($admin)
        ->get(route('media.publications.index'))
        ->assertOk();
});

it('blocks a non-Media-business-type company from media routes', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $company = Company::create([
        'company_name' => 'Retail Shop',
        'business_type' => 'Trading', // not Media
        'status' => true,
    ]);

    $user = User::factory()->create(['company_id' => $company->id]);
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get(route('media.publications.index'))
        ->assertForbidden();
});
