<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fix #7 — Account Code Range পুনর্বিন্যাস
 * ──────────────────────────────────────────
 *
 * ❌ পুরাতন range (ভুল):
 *   Asset     1001–1999
 *   Expense   2001–2999   ← Liability-র জায়গায় Expense ছিল!
 *   Liability 3000–3999
 *   Equity    4000–4999
 *   Income    5000–5999
 *
 * ✅ নতুন range (Bangladesh standard):
 *   Asset     1000–1999   (কোনো পরিবর্তন নেই)
 *   Liability 2000–2999   ← Expense সরিয়ে Liability এখানে
 *   Equity    3000–3999   ← এক ধাপ সরেছে
 *   Income    4000–4999   ← এক ধাপ সরেছে
 *   Expense   5000–5999   ← নতুন জায়গা
 *
 * ──────────────────────────────────────────────────────────────────
 * ⚠️  WARNING — এই migration চালানোর আগে:
 *
 *  1. Production DB backup নিন।
 *  2. 2001–2999 range-এ কোনো Expense account আছে কিনা চেক করুন:
 *
 *     SELECT id, account_code, account_name, account_type
 *     FROM accounts
 *     WHERE account_code BETWEEN 2001 AND 2999
 *     AND deleted_at IS NULL;
 *
 *  3. 3000–3999-এ Liability, 4000–4999-এ Equity, 5000–5999-এ Income
 *     আছে কিনা চেক করুন — সেগুলোর নতুন code conflict করবে।
 *
 *  4. যদি conflict থাকে, manually ঠিক করুন অথবা এই migration
 *     skip করুন এবং শুধু Account::CODE_RANGES constant update করুন
 *     (নতুন account-এর জন্য নতুন range কাজ করবে, পুরাতন unchanged)।
 * ──────────────────────────────────────────────────────────────────
 */
return new class extends Migration
{
    /**
     * Step 1: Temp range-এ সরাও (conflict এড়াতে)
     * Step 2: নতুন range-এ রাখো
     *
     * Offset strategy ব্যবহার করা হচ্ছে যাতে intermediate conflict না হয়।
     */
    public function up(): void
    {
        DB::transaction(function (): void {

            // ── Step 1: সব existing code-কে temp range-এ সরাও ──────────
            // (90000+ range-এ) যাতে নতুন code assign করার সময় conflict না হয়।

            // Expense: 2001–2999 → 92001–92999 (temp)
            DB::statement("
                UPDATE accounts
                SET account_code = account_code + 90000
                WHERE account_type = 'Expense'
                  AND account_code BETWEEN 2001 AND 2999
            ");

            // Liability: 3000–3999 → 93000–93999 (temp)
            DB::statement("
                UPDATE accounts
                SET account_code = account_code + 90000
                WHERE account_type = 'Liability'
                  AND account_code BETWEEN 3000 AND 3999
            ");

            // Equity: 4000–4999 → 94000–94999 (temp)
            DB::statement("
                UPDATE accounts
                SET account_code = account_code + 90000
                WHERE account_type = 'Equity'
                  AND account_code BETWEEN 4000 AND 4999
            ");

            // Income: 5000–5999 → 95000–95999 (temp)
            DB::statement("
                UPDATE accounts
                SET account_code = account_code + 90000
                WHERE account_type = 'Income'
                  AND account_code BETWEEN 5000 AND 5999
            ");

            // ── Step 2: নতুন range-এ রাখো ──────────────────────────────

            // Expense: 92001–92999 → 5001–5999 (নতুন: 5000–5999)
            DB::statement("
                UPDATE accounts
                SET account_code = account_code - 90000 + 3000
                WHERE account_type = 'Expense'
                  AND account_code BETWEEN 92001 AND 92999
            ");
            // 92001 - 90000 + 3000 = 5001 ✅, 92999 - 90000 + 3000 = 5999 ✅

            // Liability: 93000–93999 → 2000–2999 (নতুন: 2000–2999)
            DB::statement("
                UPDATE accounts
                SET account_code = account_code - 90000 - 1000
                WHERE account_type = 'Liability'
                  AND account_code BETWEEN 93000 AND 93999
            ");
            // 93000 - 90000 - 1000 = 2000 ✅, 93999 - 90000 - 1000 = 2999 ✅

            // Equity: 94000–94999 → 3000–3999 (নতুন: 3000–3999)
            DB::statement("
                UPDATE accounts
                SET account_code = account_code - 91000
                WHERE account_type = 'Equity'
                  AND account_code BETWEEN 94000 AND 94999
            ");
            // 94000 - 91000 = 3000 ✅, 94999 - 91000 = 3999 ✅

            // Income: 95000–95999 → 4000–4999 (নতুন: 4000–4999)
            DB::statement("
                UPDATE accounts
                SET account_code = account_code - 91000
                WHERE account_type = 'Income'
                  AND account_code BETWEEN 95000 AND 95999
            ");
            // 95000 - 91000 = 4000 ✅, 95999 - 91000 = 4999 ✅

        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {

            // Reverse: নতুন range → temp → পুরাতন range

            // Expense: 5001–5999 → temp
            DB::statement("
                UPDATE accounts
                SET account_code = account_code + 90000
                WHERE account_type = 'Expense'
                  AND account_code BETWEEN 5001 AND 5999
            ");

            // Liability: 2000–2999 → temp
            DB::statement("
                UPDATE accounts
                SET account_code = account_code + 90000
                WHERE account_type = 'Liability'
                  AND account_code BETWEEN 2000 AND 2999
            ");

            // Equity: 3000–3999 → temp
            DB::statement("
                UPDATE accounts
                SET account_code = account_code + 90000
                WHERE account_type = 'Equity'
                  AND account_code BETWEEN 3000 AND 3999
            ");

            // Income: 4000–4999 → temp
            DB::statement("
                UPDATE accounts
                SET account_code = account_code + 90000
                WHERE account_type = 'Income'
                  AND account_code BETWEEN 4000 AND 4999
            ");

            // temp → পুরাতন range
            DB::statement("UPDATE accounts SET account_code = account_code - 87000 WHERE account_type = 'Expense'  AND account_code BETWEEN 95001 AND 95999");
            DB::statement("UPDATE accounts SET account_code = account_code - 87000 WHERE account_type = 'Liability' AND account_code BETWEEN 92000 AND 92999");
            DB::statement("UPDATE accounts SET account_code = account_code - 87000 WHERE account_type = 'Equity'   AND account_code BETWEEN 93000 AND 93999");
            DB::statement("UPDATE accounts SET account_code = account_code - 87000 WHERE account_type = 'Income'   AND account_code BETWEEN 94000 AND 94999");
        });
    }
};