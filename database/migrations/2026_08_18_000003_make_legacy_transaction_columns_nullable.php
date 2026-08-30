<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The original transactions table was built for single-entry
 * bookkeeping and made transaction_date, voucher_no,
 * transaction_type, and amount all required (NOT NULL, no default).
 *
 * The voucher module never writes to these old columns anymore,
 * so every voucher insert fails until they are nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'transaction_date')) {
                $table->date('transaction_date')->nullable()->change();
            }
            if (Schema::hasColumn('transactions', 'voucher_no')) {
                $table->string('voucher_no')->nullable()->change();
            }
            if (Schema::hasColumn('transactions', 'transaction_type')) {
                $table->enum('transaction_type', ['Income', 'Expense', 'Journal'])->nullable()->change();
            }
            if (Schema::hasColumn('transactions', 'amount')) {
                $table->decimal('amount', 15, 2)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        // Intentionally left nullable on rollback too.
    }
};