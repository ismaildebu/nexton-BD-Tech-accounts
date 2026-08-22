<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fix #1 — Duplicate debit/credit columns পরিষ্কার করা
 * --------------------------------------------------------
 * সমস্যা:
 *   ledger_entries      → 'debit' + 'debit_amount'  (একই কাজ, দুটো column)
 *   transaction_details → 'debit' + 'debit_amount'  (একই কাজ, দুটো column)
 *
 * সমাধান:
 *   1. নিশ্চিত করো পুরাতন column-এর data নতুন column-এ আছে (safety copy)
 *   2. পুরাতন 'debit' ও 'credit' column drop করো
 *
 * NOTE: down() পুরাতন column ফিরিয়ে আনে — rollback safe।
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------------
        // Step 1 — Safety: যদি কোনো row-এ debit_amount = 0 কিন্তু debit > 0
        //          থাকে, সেটা copy করো (পুরাতন data থেকে নতুনে)
        // ---------------------------------------------------------------
        DB::statement('
            UPDATE ledger_entries
            SET
                debit_amount  = CASE WHEN debit_amount = 0 AND debit  > 0 THEN debit  ELSE debit_amount  END,
                credit_amount = CASE WHEN credit_amount = 0 AND credit > 0 THEN credit ELSE credit_amount END
        ');

        DB::statement('
            UPDATE transaction_details
            SET
                debit_amount  = CASE WHEN debit_amount = 0 AND debit  > 0 THEN debit  ELSE debit_amount  END,
                credit_amount = CASE WHEN credit_amount = 0 AND credit > 0 THEN credit ELSE credit_amount END
        ');

        // ---------------------------------------------------------------
        // Step 2 — ledger_entries থেকে পুরাতন column drop
        // ---------------------------------------------------------------
        Schema::table('ledger_entries', function (Blueprint $table): void {
            // Foreign key / index আগে drop করতে হয় না — শুধু plain column
            $table->dropColumn(['debit', 'credit']);
        });

        // ---------------------------------------------------------------
        // Step 3 — transaction_details থেকে পুরাতন column drop
        // ---------------------------------------------------------------
        Schema::table('transaction_details', function (Blueprint $table): void {
            $table->dropColumn(['debit', 'credit']);
        });
    }

    public function down(): void
    {
        // ledger_entries — পুরাতন column ফিরিয়ে আনো এবং data copy করো
        Schema::table('ledger_entries', function (Blueprint $table): void {
            $table->decimal('debit', 18, 2)->default(0)->after('credit_amount');
            $table->decimal('credit', 18, 2)->default(0)->after('debit');
        });

        DB::statement('
            UPDATE ledger_entries
            SET debit = debit_amount, credit = credit_amount
        ');

        // transaction_details — পুরাতন column ফিরিয়ে আনো এবং data copy করো
        Schema::table('transaction_details', function (Blueprint $table): void {
            $table->decimal('debit', 12, 2)->default(0)->after('credit_amount');
            $table->decimal('credit', 12, 2)->default(0)->after('debit');
        });

        DB::statement('
            UPDATE transaction_details
            SET debit = debit_amount, credit = credit_amount
        ');
    }
};