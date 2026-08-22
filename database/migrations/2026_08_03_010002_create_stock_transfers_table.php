<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: stock_transfers টেবিল তৈরি করে।
 *
 * পূর্বের প্রজেক্ট স্ট্যান্ডার্ড অনুযায়ী foreign key constraint,
 * timestamps এবং সঠিক ডেটা টাইপ ব্যবহার করা হয়েছে।
 */
return new class extends Migration
{
    /**
     * মাইগ্রেশন রান করার সময় এক্সিকিউট হয়।
     */
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('cascade');

            $table->foreignId('from_warehouse_id')
                ->constrained('warehouses')
                ->onDelete('cascade');

            $table->foreignId('to_warehouse_id')
                ->constrained('warehouses')
                ->onDelete('cascade');

            $table->unsignedInteger('quantity');
            $table->date('transfer_date');

            $table->timestamps();
        });
    }

    /**
     * মাইগ্রেশন রোলব্যাক করার সময় এক্সিকিউট হয়।
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};

/**
 * ------------------------------------------------------------------
 * ডকুমেন্টেশন ও টেস্টিং নির্দেশনা
 * ------------------------------------------------------------------
 * উদ্দেশ্য:
 *   stock_transfers টেবিল তৈরি করা, যেখানে product_id,
 *   from_warehouse_id, to_warehouse_id ফরেইন কী হিসেবে থাকবে।
 *
 * টেস্টিং ধাপ:
 *   1. php artisan migrate কমান্ড চালান।
 *   2. ডাটাবেজে stock_transfers টেবিল তৈরি হয়েছে কিনা যাচাই করুন।
 *   3. products ও warehouses টেবিল আগে থেকে থাকা আবশ্যক, নাহলে
 *      foreign key constraint error আসবে।
 *   4. রোলব্যাক টেস্ট করতে php artisan migrate:rollback চালান।
 * ------------------------------------------------------------------
 */