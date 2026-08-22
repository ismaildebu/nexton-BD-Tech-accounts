<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {

            $table->foreignId('debit_account_id')
                  ->nullable()
                  ->after('account_id')
                  ->constrained('accounts');

            $table->foreignId('credit_account_id')
                  ->nullable()
                  ->after('debit_account_id')
                  ->constrained('accounts');

        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {

            $table->dropForeign(['debit_account_id']);
            $table->dropForeign(['credit_account_id']);

            $table->dropColumn([
                'debit_account_id',
                'credit_account_id'
            ]);

        });
    }
};