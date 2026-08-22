<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
{
    $this->call([
        // Roles/permissions must exist before any user can be granted
        // access (e.g. companies.manage), and account templates must
        // exist before the "Create Company" form has anything to select.
        RoleAndPermissionSeeder::class,
        AccountTemplateSeeder::class,
        VoucherTypeSeeder::class,
    ]);
}
}