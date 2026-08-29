<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // সব existing users-দের email verify করা হিসেবে মার্ক করুন
        // এতে তারা app-এ login করতে পারবে মিডলওয়্যার block না করে
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update([
                'email_verified_at' => DB::raw('NOW()'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Production-এ downgrade করবেন না, কিন্তু dev-এ local reversal:
        // সব users-দের unverified করা (optional)
        // DB::table('users')->update(['email_verified_at' => null]);
    }
};