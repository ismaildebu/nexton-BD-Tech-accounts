<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            if (! Schema::hasColumn('transactions', 'financial_year_id')) {
                $table->foreignId('financial_year_id')
                    ->nullable()
                    ->after('company_id')
                    ->constrained('financial_years')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('transactions', 'voucher_type_id')) {
                $table->foreignId('voucher_type_id')
                    ->nullable()
                    ->after('financial_year_id')
                    ->constrained('voucher_types')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('transactions', 'narration')) {
                $table->text('narration')
                    ->nullable()
                    ->after('transaction_date');
            }

            if (! Schema::hasColumn('transactions', 'status')) {
                $table->enum('status', [
                    'Draft',
                    'Posted',
                    'Cancelled',
                ])->default('Posted');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            if (Schema::hasColumn('transactions', 'financial_year_id')) {
                $table->dropForeign(['financial_year_id']);
                $table->dropColumn('financial_year_id');
            }

            if (Schema::hasColumn('transactions', 'voucher_type_id')) {
                $table->dropForeign(['voucher_type_id']);
                $table->dropColumn('voucher_type_id');
            }

            if (Schema::hasColumn('transactions', 'narration')) {
                $table->dropColumn('narration');
            }

            if (Schema::hasColumn('transactions', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};