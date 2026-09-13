<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\VoucherType;
use Illuminate\Console\Command;

class AuditOpeningBalances extends Command
{
    protected $signature = 'audit:opening-balances';
    protected $description = 'Verify opening balance transactions are balanced';

    public function handle()
{
    // সব opening voucher types খুঁজুন
    $obTypes = VoucherType::where('nature', 'opening')->get();

    if ($obTypes->isEmpty()) {
        $this->error('No Opening Voucher types found');
        return 1;
    }

    $this->info("Found " . $obTypes->count() . " Opening Voucher Types\n");

    $grandTotal = 0;
    $grandErrors = [];

    foreach ($obTypes as $obType) {
        $this->line("Checking Company ID: {$obType->company_id}, Type: {$obType->name} (ID: {$obType->id})");

        $transactions = Transaction::where('voucher_type_id', $obType->id)
            ->orderBy('id')
            ->get();

        if ($transactions->isEmpty()) {
            $this->line("  → No transactions found\n");
            continue;
        }

        $this->line("  → Total: {$transactions->count()} transactions");

        foreach ($transactions as $txn) {
            $isBalanced = bccomp(
                (string) $txn->total_debit,
                (string) $txn->total_credit,
                4
            ) === 0;

            if ($isBalanced) {
                $this->line("    ✓ #{$txn->id} ({$txn->voucher_number}): {$txn->total_debit} = {$txn->total_credit}");
                $grandTotal++;
            } else {
                $diff = abs((float)$txn->total_debit - (float)$txn->total_credit);
                $this->line("    ✗ #{$txn->id} ({$txn->voucher_number}): {$txn->total_debit} ≠ {$txn->total_credit} (Diff: {$diff})");
                $grandErrors[] = $txn->id;
            }
        }
        $this->line("");
    }

    $this->info(str_repeat('=', 80));

    if (empty($grandErrors)) {
        $this->info("✅ ALL {$grandTotal} TRANSACTIONS BALANCED! ✅");
        return 0;
    } else {
        $this->error("❌ ERROR: {$grandTotal} balanced, " . count($grandErrors) . " unbalanced");
        return 1;
    }
}
}