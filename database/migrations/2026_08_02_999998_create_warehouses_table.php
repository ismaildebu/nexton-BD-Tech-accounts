<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: warehouses টেবিল তৈরি করে।
 *
 * এটি Stock Transfer মডিউলের জন্য প্রয়োজনীয় একটি প্রাথমিক (minimal)
 * warehouses টেবিল — Nexton Accounts প্রজেক্টে আগে থেকে এই টেবিল ছিল না।
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->nullable()
                ->constrained('companies')
                ->onDelete('cascade');

            $table->string('name');
            $table->string('location')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};

/**
 * ------------------------------------------------------------------
 * ডকুমেন্টেশন ও টেস্টিং নির্দেশনা
 * ------------------------------------------------------------------
 * উদ্দেশ্য:
 *   warehouses টেবিল তৈরি করা, যা stock_transfers টেবিলের
 *   from_warehouse_id ও to_warehouse_id ফরেইন কী রেফারেন্সের জন্য
 *   আবশ্যক।
 *
 * টেস্টিং ধাপ:
 *   1. php artisan migrate চালান।
 *   2. php artisan tinker এ Schema::hasTable('warehouses') true
 *      দেখাচ্ছে কিনা যাচাই করুন।
 *   3. Warehouse::create(['name' => 'Main Warehouse']) দিয়ে একটি
 *      রেকর্ড তৈরি করে দেখুন।
 * ------------------------------------------------------------------
 */