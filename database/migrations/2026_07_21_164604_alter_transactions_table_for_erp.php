<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {

            $table->foreignId('financial_year_id')
                ->nullable()
                ->after('company_id')
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('voucher_type_id')
                ->nullable()
                ->after('financial_year_id')
                ->constrained()
                ->nullOnDelete();

            $table->text('narration')
                ->nullable()
                ->after('transaction_date');

            $table->enum('status', [
                'Draft',
                'Posted',
                'Cancelled'
            ])->default('Posted');

        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {

            $table->dropForeign(['financial_year_id']);
            $table->dropForeign(['voucher_type_id']);

            $table->dropColumn([
                'financial_year_id',
                'voucher_type_id',
                'narration',
                'status',
            ]);

        });
    }
};