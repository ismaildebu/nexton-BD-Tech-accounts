<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `transactions.account_id` was created as a required (NOT NULL) column
 * back when a transaction had a single account. The double-entry voucher
 * redesign moved line items into `transaction_details`
 * (debit_account_id / credit_account_id were already made nullable at
 * that point — this column was missed).
 *
 * It is no longer written to anywhere in the app, so it's safe to make
 * nullable rather than drop outright (avoids breaking any old row that
 * still references it).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('transactions', 'account_id') && DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `transactions` MODIFY `account_id` BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('transactions', 'account_id')) {
            // Only safe to re-add NOT NULL if no NULLs exist; skip silently otherwise.
            $hasNulls = DB::table('transactions')->whereNull('account_id')->exists();

            if (! $hasNulls) {
                DB::statement('ALTER TABLE `transactions` MODIFY `account_id` BIGINT UNSIGNED NOT NULL');
            }
        }
    }
};
