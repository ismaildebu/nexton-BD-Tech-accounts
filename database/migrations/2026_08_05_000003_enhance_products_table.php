<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('category')->nullable()->after('sku');
            $table->string('unit')->default('pcs')->after('category');
            $table->decimal('purchase_price', 15, 2)->default(0)->after('unit');
            $table->decimal('sale_price', 15, 2)->default(0)->after('purchase_price');
            $table->integer('reorder_level')->default(0)->after('sale_price');
            $table->text('description')->nullable()->after('reorder_level');
            $table->boolean('is_active')->default(true)->after('description');
        });

        // Product stock per warehouse
        Schema::create('product_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->decimal('quantity', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->unique(['product_id', 'warehouse_id']);
        });

        // Stock movement log
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->enum('type', ['in', 'out', 'transfer', 'adjustment']);
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->string('reference')->nullable(); // PO number, SO number, etc.
            $table->string('reference_type')->nullable(); // purchase, sale, transfer
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->date('movement_date');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('product_stocks');
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['category', 'unit', 'purchase_price', 'sale_price', 'reorder_level', 'description', 'is_active']);
        });
    }
};