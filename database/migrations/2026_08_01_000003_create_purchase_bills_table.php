<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_bills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->string('bill_number')->unique();
            $table->date('bill_date');
            $table->date('due_date')->nullable();
            $table->enum('status', ['Unpaid', 'Partial', 'Paid'])->default('Unpaid');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('due_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('set null');
        });

        Schema::create('purchase_bill_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_bill_id');
            $table->string('item_name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 15, 2)->default(1);
            $table->string('unit')->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('purchase_bill_id')->references('id')->on('purchase_bills')->onDelete('cascade');
        });

        Schema::create('purchase_bill_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_bill_id');
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method')->default('Cash');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('purchase_bill_id')->references('id')->on('purchase_bills')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_bill_payments');
        Schema::dropIfExists('purchase_bill_items');
        Schema::dropIfExists('purchase_bills');
    }
};