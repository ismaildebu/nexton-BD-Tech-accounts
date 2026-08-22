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
        Schema::table('transaction_details', function (Blueprint $table): void {

            if (! Schema::hasColumn('transaction_details', 'description')) {
                $table->text('description')->nullable()->after('credit');
            }
        });

        Schema::table('transaction_details', function (Blueprint $table): void {

            if (! Schema::hasColumn('transaction_details', 'debit_amount')) {
                $table->decimal('debit_amount', 18, 4)->default(0)->after('description');
            }

            if (! Schema::hasColumn('transaction_details', 'credit_amount')) {
                $table->decimal('credit_amount', 18, 4)->default(0)->after('debit_amount');
            }
        });

        // পুরানো data copy করুন
        DB::statement('UPDATE transaction_details SET debit_amount = debit, credit_amount = credit');
    }

    public function down(): void
    {
        Schema::table('transaction_details', function (Blueprint $table): void {
            $table->dropColumn([
                'description',
                'debit_amount',
                'credit_amount',
            ]);
        });
    }
};