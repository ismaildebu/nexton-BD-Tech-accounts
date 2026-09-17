<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * সমস্যা #3: Opening Balance ডেসিমেল নির্ভুলতা সমন্বয়
     * 
     * পরিবর্তন: decimal(18,2) → decimal(18,4)
     * কারণ: অন্যান্য মুদ্রা ক্ষেত্র decimal(18,4) ব্যবহার করে
     * এটা সামঞ্জস্যপূর্ণ প্রিসিশন নিশ্চিত করে
     */
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->decimal('opening_balance', 18, 4)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->decimal('opening_balance', 18, 2)->change();
        });
    }
};