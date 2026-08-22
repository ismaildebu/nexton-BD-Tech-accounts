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

            if (! Schema::hasColumn('ledger_entries', 'transaction_id')) {
                $table->foreignId('transaction_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('transactions')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('ledger_entries', 'financial_year_id')) {
                $table->foreignId('financial_year_id')
                    ->nullable()
                    ->after('transaction_id')
                    ->constrained('financial_years')
                    ->restrictOnDelete();
            }

            if (! Schema::hasColumn('ledger_entries', 'voucher_type_id')) {
                $table->foreignId('voucher_type_id')
                    ->nullable()
                    ->after('financial_year_id')
                    ->constrained('voucher_types')
                    ->restrictOnDelete();
            }

            if (! Schema::hasColumn('ledger_entries', 'voucher_number')) {
                $table->string('voucher_number', 50)
                    ->nullable()
                    ->after('voucher_type_id');
            }

            if (! Schema::hasColumn('ledger_entries', 'voucher_date')) {
                $table->date('voucher_date')
                    ->nullable()
                    ->after('voucher_number');
            }

            if (! Schema::hasColumn('ledger_entries', 'debit_amount')) {
                $table->decimal('debit_amount', 18, 4)
                    ->default(0)
                    ->after('voucher_date');
            }

            if (! Schema::hasColumn('ledger_entries', 'credit_amount')) {
                $table->decimal('credit_amount', 18, 4)
                    ->default(0)
                    ->after('debit_amount');
            }

            if (! Schema::hasColumn('ledger_entries', 'is_reversed')) {
                $table->boolean('is_reversed')
                    ->default(false)
                    ->after('credit_amount');
            }

            if (! Schema::hasColumn('ledger_entries', 'description')) {
                $table->text('description')
                    ->nullable()
                    ->after('is_reversed');
            }

            if (! Schema::hasColumn('ledger_entries', 'deleted_at')) {
                $table->softDeletes();
            }
        });

       
                // Add indexes only if they don't exist (driver-agnostic, works
        // on both MySQL and sqlite — sqlite is what `php artisan test`
        // uses, so a raw MySQL "SHOW INDEX" here breaks every test run).
        $existingIndexes = array_column(Schema::getIndexes('ledger_entries'), 'name');


        

        Schema::table('ledger_entries', function (Blueprint $table) use ($existingIndexes): void {
            if (! in_array('idx_le_transaction', $existingIndexes, true)) {
                $table->index('transaction_id', 'idx_le_transaction');
            }
            if (! in_array('idx_le_company_fy', $existingIndexes, true)) {
                $table->index(['company_id', 'financial_year_id'], 'idx_le_company_fy');
            }
            if (! in_array('idx_le_account_date', $existingIndexes, true)) {
                $table->index(['account_id', 'voucher_date'], 'idx_le_account_date');
            }
            if (! in_array('idx_le_voucher_number', $existingIndexes, true)) {
                $table->index('voucher_number', 'idx_le_voucher_number');
            }
            if (! in_array('idx_le_is_reversed', $existingIndexes, true)) {
                $table->index('is_reversed', 'idx_le_is_reversed');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('transaction_id');
            $table->dropConstrainedForeignId('financial_year_id');
            $table->dropConstrainedForeignId('voucher_type_id');
            $table->dropColumn([
                'voucher_number',
                'voucher_date',
                'debit_amount',
                'credit_amount',
                'is_reversed',
                'description',
                'deleted_at',
            ]);
        });
    }
};