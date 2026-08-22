<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Invoice::$fillable already listed 'invoice_number', but no
 * migration ever actually added the column — so it was silently
 * ignored on every Invoice::create() call. This adds it for real.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('invoice_number')->nullable()->after('customer_id');
            $table->unique('invoice_number');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['invoice_number']);
            $table->dropColumn('invoice_number');
        });
    }
};