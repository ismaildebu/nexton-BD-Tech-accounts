<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bkash_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('payment_id')->unique();
            $table->string('trx_id')->nullable()->unique();
            $table->string('merchant_invoice_number')->unique();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('BDT');
            $table->enum('status', ['initiated','pending','completed','failed','cancelled','refunded'])->default('initiated');
            $table->json('callback_response')->nullable();
            $table->json('execute_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'status']);
            $table->index(['invoice_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bkash_payments');
    }
};