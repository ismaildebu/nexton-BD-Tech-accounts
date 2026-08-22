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
        Schema::create('ledger_entries', function (Blueprint $table) {

            $table->id();

            $table->foreignId('transaction_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('account_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('entry_date');

            $table->decimal('debit',18,2)->default(0);

            $table->decimal('credit',18,2)->default(0);

            $table->text('description')->nullable();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};