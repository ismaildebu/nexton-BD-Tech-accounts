<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: salaries টেবিল তৈরি করে।
 *
 * পূর্বের প্রজেক্ট স্ট্যান্ডার্ড অনুযায়ী foreign key constraint,
 * decimal ফিল্ড এবং timestamps ব্যবহার করা হয়েছে।
 */
return new class extends Migration
{
    /**
     * মাইগ্রেশন রান করার সময় এক্সিকিউট হয়।
     */
    public function up(): void
    {
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->onDelete('cascade');

            $table->decimal('basic_salary', 12, 2);
            $table->decimal('allowances', 12, 2)->default(0);
            $table->decimal('deductions', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2);
            $table->date('salary_month');

            $table->timestamps();

            // একই কর্মচারীর একই মাসে একাধিক বেতন রেকর্ড আটকাতে
            $table->unique(['employee_id', 'salary_month']);
        });
    }

    /**
     * মাইগ্রেশন রোলব্যাক করার সময় এক্সিকিউট হয়।
     */
    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};

/**
 * ------------------------------------------------------------------
 * ডকুমেন্টেশন ও টেস্টিং নির্দেশনা
 * ------------------------------------------------------------------
 * উদ্দেশ্য:
 *   salaries টেবিল তৈরি করা, যেখানে employee_id ফরেইন কী হিসেবে
 *   থাকবে এবং প্রতিটি employee_id + salary_month সংমিশ্রণ ইউনিক হবে
 *   (একই মাসে ডুপ্লিকেট বেতন এন্ট্রি আটকাতে)।
 *
 * টেস্টিং ধাপ:
 *   1. php artisan migrate কমান্ড চালান।
 *   2. ডাটাবেজে salaries টেবিল তৈরি হয়েছে কিনা যাচাই করুন।
 *   3. employees টেবিল আগে থেকে থাকা আবশ্যক, নাহলে foreign key
 *      constraint error আসবে।
 *   4. একই employee_id + salary_month দিয়ে দুইবার ইনসার্ট করার
 *      চেষ্টা করলে unique constraint error আসছে কিনা যাচাই করুন।
 *   5. রোলব্যাক টেস্ট করতে php artisan migrate:rollback চালান।
 * ------------------------------------------------------------------
 */