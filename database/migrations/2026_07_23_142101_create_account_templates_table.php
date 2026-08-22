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
        Schema::create('account_templates', function (Blueprint $table) {

            $table->id();

            $table->integer('account_code')->unique();

            $table->string('account_name');

            $table->enum('account_type', [
                'Asset',
                'Liability',
                'Equity',
                'Income',
                'Expense'
            ]);

            $table->string('nature')->default('General');

            $table->enum('balance_type', [
                'Debit',
                'Credit'
            ]);

            $table->string('industry')->default('All');

            $table->string('business_type')->nullable();

            $table->boolean('is_system')->default(true);

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_templates');
    }
};