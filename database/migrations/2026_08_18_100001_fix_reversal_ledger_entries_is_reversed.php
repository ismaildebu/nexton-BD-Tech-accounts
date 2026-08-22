<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One-time data fix.
 *
 * পুরনো সমস্যা:
 *   LedgerPostingService::reverseLedgerEntries() reversal rows insert করত
 *   is_reversed = false দিয়ে। ফলে Ledger ও Balance Sheet-এ cancelled
 *   voucher-এর reversal entries ভুলভাবে দেখা যেত।
 *
 * এই migration:
 *   যেসব ledger_entry-র description 'Reversal:' দিয়ে শুরু এবং
 *   is_reversed = false, সেগুলোকে is_reversed = true করে দেবে।
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('ledger_entries')
            ->where('is_reversed', false)
            ->where('description', 'LIKE', 'Reversal:%')
            ->update(['is_reversed' => true]);
    }

    public function down(): void
    {
        // Intentionally irreversible — rolling back would re-corrupt data.
    }
};