<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
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
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasColumn('transactions', 'transaction_date')) {
            DB::statement('ALTER TABLE `transactions` MODIFY `transaction_date` DATE NULL');
        }
        if (Schema::hasColumn('transactions', 'voucher_no')) {
            DB::statement('ALTER TABLE `transactions` MODIFY `voucher_no` VARCHAR(255) NULL');
        }
        if (Schema::hasColumn('transactions', 'transaction_type')) {
            DB::statement("ALTER TABLE `transactions` MODIFY `transaction_type` ENUM('Income','Expense','Journal') NULL");
        }
        if (Schema::hasColumn('transactions', 'amount')) {
            DB::statement('ALTER TABLE `transactions` MODIFY `amount` DECIMAL(15,2) NULL');
        }
    }

    public function down(): void
    {
        // Intentionally left nullable on rollback too.
    }
};