<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Create the super admin user if it does not exist.
     *
     * Note: RoleAndPermissionSeeder should run before this seeder.
     */
    public function run(): void
    {
        $superAdminRole = Role::where('name', 'super-admin')
            ->where('guard_name', 'web')
            ->first();

        if (!$superAdminRole) {
            $this->command->warn(
                'Warning: "super-admin" role does not exist. ' .
                'Run RoleAndPermissionSeeder first!'
            );

            return;
        }

        $email = config('app.super_admin.email');
        $password = config('app.super_admin.password');

        if (!$email || !$password) {
            $this->command->error(
                'Super admin credentials are not configured.'
            );

            return;
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'              => 'Super Admin',
                'password'          => Hash::make($password),
                'role'              => 'Super Admin',
                'status'            => true,
                'company_id'        => null,
                'email_verified_at' => now(),
            ]
        );

        $user->syncRoles(['super-admin']);

        $this->command->info(
            "Super Admin user verified: {$user->email} with role: super-admin"
        );
    }
}