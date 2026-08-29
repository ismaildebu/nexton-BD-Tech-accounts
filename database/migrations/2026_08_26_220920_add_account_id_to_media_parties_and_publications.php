<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_parties', function (Blueprint $table) {
            $table->foreignId('account_id')
                ->nullable()
                ->after('balance_type')
                ->constrained('accounts')
                ->nullOnDelete();
        });

        Schema::table('publications', function (Blueprint $table) {
            $table->foreignId('sales_account_id')
                ->nullable()
                ->after('selling_price')
                ->constrained('accounts')
                ->nullOnDelete();

            $table->foreignId('sales_return_account_id')
                ->nullable()
                ->after('sales_account_id')
                ->constrained('accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('media_parties', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Account::class);
            $table->dropColumn('account_id');
        });

        Schema::table('publications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sales_account_id');
            $table->dropConstrainedForeignId('sales_return_account_id');
        });
    }
};