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
        Schema::create('accounts', function (Blueprint $table) {

            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->integer('account_code')->nullable();

            $table->string('account_name');

            $table->enum('account_type', [
                'Asset',
                'Liability',
                'Equity',
                'Income',
                'Expense',
            ]);

            $table->string('nature')->default('General');

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();

            $table->unsignedTinyInteger('level')->default(1);

            $table->string('color', 20)->nullable();

            $table->boolean('is_system')->default(false);

            $table->boolean('is_active')->default(true);

            $table->decimal('opening_balance', 15, 2)->default(0);

            $table->enum('balance_type', [
                'Debit',
                'Credit',
            ])->default('Debit');

            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};