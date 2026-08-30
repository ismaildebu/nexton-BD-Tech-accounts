<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `transactions.account_id` was created as a required (NOT NULL) column
 * back when a transaction had a single account. The double-entry voucher
 * redesign moved line items into `transaction_details`
 * (debit_account_id / credit_account_id were already made nullable at
 * that point - this column was missed).
 *
 * It is no longer written to anywhere in the app, so it's safe to make
 * nullable rather than drop outright (avoids breaking any old row that
 * still references it).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('transactions', 'account_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('account_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('transactions', 'account_id')) {
            $hasNulls = DB::table('transactions')->whereNull('account_id')->exists();

            if (! $hasNulls) {
                Schema::table('transactions', function (Blueprint $table) {
                    $table->unsignedBigInteger('account_id')->nullable(false)->change();
                });
            }
        }
    }
};