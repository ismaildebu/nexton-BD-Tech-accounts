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
            $table->string('reference_type')
                ->nullable()
                ->after('reference_number');

            $table->unsignedBigInteger('reference_id')
                ->nullable()
                ->after('reference_type');

            $table->index(
                ['company_id', 'reference_type', 'reference_id'],
                'transactions_company_reference_index'
            );
        });

        Schema::table('ledger_entries', function (Blueprint $table): void {
            $table->string('reference_type')
                ->nullable()
                ->after('voucher_type_id');

            $table->unsignedBigInteger('reference_id')
                ->nullable()
                ->after('reference_type');

            $table->index(
                ['company_id', 'reference_type', 'reference_id'],
                'ledger_entries_company_reference_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table): void {
            $table->dropIndex(
                'ledger_entries_company_reference_index'
            );

            $table->dropColumn([
                'reference_type',
                'reference_id',
            ]);
        });

        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropIndex(
                'transactions_company_reference_index'
            );

            $table->dropColumn([
                'reference_type',
                'reference_id',
            ]);
        });
    }
};