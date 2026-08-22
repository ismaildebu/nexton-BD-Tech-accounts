<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('voucher_number')->nullable()->after('voucher_type_id');
            $table->date('voucher_date')->nullable()->after('voucher_number');
            $table->string('reference_number')->nullable()->after('voucher_date');
            $table->decimal('total_debit', 18, 4)->default(0)->after('reference_number');
            $table->decimal('total_credit', 18, 4)->default(0)->after('total_debit');
            $table->unsignedBigInteger('posted_by')->nullable()->after('created_by');
            $table->timestamp('posted_at')->nullable()->after('posted_by');
            $table->unsignedBigInteger('cancelled_by')->nullable()->after('posted_at');
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
            $table->text('cancellation_reason')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'voucher_number',
                'voucher_date',
                'reference_number',
                'total_debit',
                'total_credit',
                'posted_by',
                'posted_at',
                'cancelled_by',
                'cancelled_at',
                'cancellation_reason',
            ]);
        });
    }
};