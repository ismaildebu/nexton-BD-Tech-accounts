<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->foreignId('account_id')
                ->nullable()
                ->after('company_id')
                ->constrained('accounts')
                ->nullOnDelete();

            $table->unique(
                'account_id',
                'bank_accounts_account_id_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropUnique('bank_accounts_account_id_unique');
            $table->dropForeign(['account_id']);
            $table->dropColumn('account_id');
        });
    }
};