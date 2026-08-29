<?php

namespace Database\Seeders;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * সুপার অ্যাডমিন ইউজার তৈরি বা আপডেট করুন।
     * 
     * নোট: এই seeder রান করার আগে RoleAndPermissionSeeder চলা উচিত।
     */
    public function run(): void
    {
        // ১. 'super-admin' role exist করে কিনা চেক করুন
        $superAdminRole = Role::where('name', 'super-admin')
            ->where('guard_name', 'web')
            ->first();

        if (!$superAdminRole) {
            $this->command->warn(
                '⚠️  Warning: "super-admin" role does not exist. ' .
                'Run RoleAndPermissionSeeder first!'
            );
            return;
        }

        // ২. সুপার অ্যাডমিন ইউজার তৈরি বা আপডেট করুন
        $user = User::updateOrCreate(
            ['email' => 'mallick.jess@gmail.com'],
            [
                'name'              => 'Super Admin',
                'email'             => 'mallick.jess@gmail.com',
                'password'          => Hash::make('624606624606'),
                'role'              => 'Super Admin',
                'status'            => true,
                'company_id'        => null,
                'email_verified_at' => now(),
            ]
        );

        // ৩. রোল sync করুন
        $user->syncRoles(['super-admin']);

        $this->command->info(
            "✅ Super Admin user created/updated: {$user->email} " .
            "with role: super-admin"
        );
    }
}