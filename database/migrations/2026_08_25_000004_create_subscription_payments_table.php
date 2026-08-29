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
        Schema::create('subscription_payments', function (Blueprint $table) {

            $table->id();

            $table->foreignId('subscription_id')
                ->constrained('subscriptions')
                ->restrictOnDelete();

            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('BDT');

            // pending | paid | failed | refunded
            $table->string('status')->default('pending');

            $table->string('payment_method')->nullable();
            $table->string('transaction_reference')->nullable();

            $table->dateTime('paid_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['subscription_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};