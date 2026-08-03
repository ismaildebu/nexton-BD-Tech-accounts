<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: stock_transfers টেবিল তৈরি করে।
 */
return new class extends Migration
{
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

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};