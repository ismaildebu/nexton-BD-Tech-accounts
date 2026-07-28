<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {

            $table->decimal('total_amount', 15, 2)
                ->default(0)
                ->after('id');

            $table->decimal('paid_amount', 15, 2)
                ->default(0)
                ->after('total_amount');

            $table->string('status')
                ->default('pending')
                ->after('paid_amount');

            $table->date('invoice_date')
                ->nullable()
                ->after('status');

            $table->date('due_date')
                ->nullable()
                ->after('invoice_date');

            $table->timestamp('paid_at')
                ->nullable()
                ->after('due_date');

        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {

            $table->dropColumn([
                'total_amount',
                'paid_amount',
                'status',
                'invoice_date',
                'due_date',
                'paid_at',
            ]);

        });
    }
};