<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            AccountTemplateSeeder::class,
            VoucherTypeSeeder::class,
            PlanSeeder::class,
            PlanFeatureSeeder::class,
            SuperAdminSeeder::class,
              SalesOrderTestSeeder::class,
        ]);
    }
}