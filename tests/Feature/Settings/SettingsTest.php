<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private Company $company1;
    private Company $company2;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

         $this->company1 = Company::create([
            'company_name' => 'Company One',
            'owner_name' => 'Owner One',
            'email' => 'company1@example.com',
            'phone' => '01700000001',
            'address' => 'Address One',
            'city' => 'Jessore',
            'country' => 'Bangladesh',
            'currency' => 'BDT',
            'currency_symbol' => '৳',
            'financial_year' => '2026-2027',
            'status' => true,
            'business_type' => 'General',
        ]);

        $this->company2 = Company::create([
            'company_name' => 'Company Two',
            'owner_name' => 'Owner Two',
            'email' => 'company2@example.com',
            'phone' => '01700000002',
            'address' => 'Address Two',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
            'currency' => 'BDT',
            'currency_symbol' => '৳',
            'financial_year' => '2026-2027',
            'status' => true,
            'business_type' => 'General',
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company1->id,
            'status' => true,
        ]);

        Auth::login($this->user);

        session([
            'company_id' => $this->company1->id,
        ]);
    }

    public function test_settings_page_can_be_opened(): void
    {
        $this->user->givePermissionTo('settings.view');

        $response = $this->get(route('settings.index'));

        $response->assertOk();
        $response->assertViewIs('settings.index');
        $response->assertViewHas('company', $this->company1);
    }

    public function test_user_without_settings_view_permission_is_denied(): void
    {
        $response = $this->get(route('settings.index'));

        $response->assertForbidden();
    }

    public function test_company_admin_can_update_own_company_settings(): void
    {
        $this->user->givePermissionTo([
            'settings.view',
            'settings.manage',
        ]);

        $response = $this->put(route('settings.update'), [
            'company_name' => 'Updated Company',
            'owner_name' => 'Updated Owner',
            'email' => 'updated@example.com',
            'phone' => '01800000000',
            'address' => 'Updated Address',
            'city' => 'Khulna',
            'country' => 'Bangladesh',
            'currency' => 'BDT',
            'currency_symbol' => '৳',
        ]);

        $response
            ->assertRedirect(route('settings.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('companies', [
            'id' => $this->company1->id,
            'company_name' => 'Updated Company',
            'owner_name' => 'Updated Owner',
            'email' => 'updated@example.com',
        ]);

        $this->assertDatabaseHas('companies', [
            'id' => $this->company2->id,
            'company_name' => 'Company Two',
        ]);
    }

    public function test_user_without_settings_manage_permission_cannot_update(): void
    {
        $this->user->givePermissionTo('settings.view');

        $response = $this->put(route('settings.update'), [
            'company_name' => 'Should Not Update',
            'owner_name' => 'Owner',
            'email' => 'test@example.com',
            'phone' => '01700000000',
            'address' => 'Address',
            'city' => 'Jessore',
            'country' => 'Bangladesh',
            'currency' => 'BDT',
            'currency_symbol' => '৳',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('companies', [
            'id' => $this->company1->id,
            'company_name' => 'Company One',
        ]);
    }

    public function test_settings_update_requires_valid_company_name(): void
    {
        $this->user->givePermissionTo([
            'settings.view',
            'settings.manage',
        ]);

        $response = $this->put(route('settings.update'), [
            'company_name' => '',
            'currency' => 'BDT',
            'currency_symbol' => '৳',
        ]);

        $response->assertSessionHasErrors([
            'company_name',
        ]);
    }

    public function test_settings_update_requires_valid_email(): void
    {
        $this->user->givePermissionTo([
            'settings.view',
            'settings.manage',
        ]);

        $response = $this->put(route('settings.update'), [
            'company_name' => 'Updated Company',
            'email' => 'invalid-email',
            'currency' => 'BDT',
            'currency_symbol' => '৳',
        ]);

        $response->assertSessionHasErrors([
            'email',
        ]);
    }
}