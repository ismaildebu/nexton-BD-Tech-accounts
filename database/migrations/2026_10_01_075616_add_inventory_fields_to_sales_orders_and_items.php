<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('warehouse_id')
                ->nullable()
                ->after('customer_id');

            $table->boolean('is_stock_deducted')
                ->default(false)
                ->after('is_accounted');

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->nullOnDelete();

            $table->index('warehouse_id');
        });

        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')
                ->nullable()
                ->after('sales_order_id');

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->nullOnDelete();

            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropIndex(['product_id']);
            $table->dropColumn('product_id');
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropIndex(['warehouse_id']);
            $table->dropColumn([
                'warehouse_id',
                'is_stock_deducted',
            ]);
        });
    }
};