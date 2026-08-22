<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TransactionDetail model has always listed sort_order as fillable
 * and cast, and VoucherService writes it on every insert, but no
 * migration ever actually added the column to transaction_details.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_details', function (Blueprint $table): void {
            if (! Schema::hasColumn('transaction_details', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('account_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transaction_details', function (Blueprint $table): void {
            if (Schema::hasColumn('transaction_details', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
        });
    }
};
