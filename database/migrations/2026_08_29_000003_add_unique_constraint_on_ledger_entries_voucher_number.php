<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Safety Check: Detect duplicates
        $duplicates = DB::table('ledger_entries')
            ->select('company_id', 'financial_year_id', 'voucher_number')
            ->groupBy('company_id', 'financial_year_id', 'voucher_number')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($duplicates > 0) {
            throw new \RuntimeException(
                "Cannot add UNIQUE constraint: {$duplicates} duplicate found."
            );
        }

        // Drop existing non-unique index
        $this->dropExistingVoucherNumberIndex();

        // Add composite UNIQUE constraint
        Schema::table('ledger_entries', function (Blueprint $table): void {
            $table->unique(
                ['company_id', 'financial_year_id', 'voucher_number'],
                'idx_le_company_fy_voucher_number_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table): void {
            $table->dropUnique('idx_le_company_fy_voucher_number_unique');
        });

        Schema::table('ledger_entries', function (Blueprint $table): void {
            $table->index('voucher_number', 'idx_le_voucher_number');
        });
    }

    private function dropExistingVoucherNumberIndex(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            try {
                DB::statement('ALTER TABLE `ledger_entries` DROP INDEX `idx_le_voucher_number`');
            } catch (\Exception $e) {
                // Index might not exist
            }
        }
    }
};