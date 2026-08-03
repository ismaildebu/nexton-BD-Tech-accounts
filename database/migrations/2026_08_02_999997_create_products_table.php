<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: products টেবিল তৈরি করে।
 *
 * এটি Stock Transfer মডিউলের জন্য প্রয়োজনীয় একটি প্রাথমিক (minimal)
 * products টেবিল — Nexton Accounts প্রজেক্টে আগে থেকে এই টেবিল ছিল না।
 * প্রয়োজনে পরবর্তীতে আরও ফিল্ড (SKU, category, unit_price ইত্যাদি)
 * যোগ করার জন্য একটি নতুন migration তৈরি করা উচিত।
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->nullable()
                ->constrained('companies')
                ->onDelete('cascade');

            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

/**
 * ------------------------------------------------------------------
 * ডকুমেন্টেশন ও টেস্টিং নির্দেশনা
 * ------------------------------------------------------------------
 * উদ্দেশ্য:
 *   products টেবিল তৈরি করা, যা stock_transfers টেবিলের product_id
 *   ফরেইন কী রেফারেন্সের জন্য আবশ্যক।
 *
 * টেস্টিং ধাপ:
 *   1. php artisan migrate চালান।
 *   2. php artisan tinker এ Schema::hasTable('products') true
 *      দেখাচ্ছে কিনা যাচাই করুন।
 *   3. Product::create(['name' => 'Test Product']) দিয়ে একটি রেকর্ড
 *      তৈরি করে দেখুন।
 * ------------------------------------------------------------------
 */