<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table): void {
            $table->dropUnique('idx_le_company_fy_voucher_number_unique');

            $table->index(
                ['company_id', 'financial_year_id', 'voucher_number'],
                'idx_le_company_fy_voucher_number'
            );
        });
    }

    public function down(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table): void {
            $table->dropIndex('idx_le_company_fy_voucher_number');

            $table->unique(
                ['company_id', 'financial_year_id', 'voucher_number'],
                'idx_le_company_fy_voucher_number_unique'
            );
        });
    }
};