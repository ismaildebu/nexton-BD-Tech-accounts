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
        Schema::create('transactions', function (Blueprint $table) {

            $table->id();

            $table->foreignId('company_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('account_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->date('transaction_date');

            $table->string('voucher_no')->unique();

            $table->enum('transaction_type', [
                'Income',
                'Expense',
                'Journal'
            ]);

            $table->decimal('amount', 15, 2);

            $table->text('description')->nullable();

            $table->foreignId('created_by')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};