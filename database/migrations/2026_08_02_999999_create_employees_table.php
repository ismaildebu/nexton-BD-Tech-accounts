<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: employees টেবিল তৈরি করে।
 *
 * এটি Salary মডিউলের জন্য প্রয়োজনীয় একটি প্রাথমিক (minimal)
 * employees টেবিল — Nexton Accounts প্রজেক্টে আগে থেকে এই টেবিল ছিল না।
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->nullable()
                ->constrained('companies')
                ->onDelete('cascade');

            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('designation')->nullable();
            $table->string('phone')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};

/**
 * ------------------------------------------------------------------
 * ডকুমেন্টেশন ও টেস্টিং নির্দেশনা
 * ------------------------------------------------------------------
 * উদ্দেশ্য:
 *   employees টেবিল তৈরি করা, যা salaries টেবিলের employee_id
 *   ফরেইন কী রেফারেন্সের জন্য আবশ্যক।
 *
 * টেস্টিং ধাপ:
 *   1. php artisan migrate চালান।
 *   2. php artisan tinker এ Schema::hasTable('employees') true
 *      দেখাচ্ছে কিনা যাচাই করুন।
 *   3. Employee::create(['name' => 'Test Employee']) দিয়ে একটি
 *      রেকর্ড তৈরি করে দেখুন।
 * ------------------------------------------------------------------
 */