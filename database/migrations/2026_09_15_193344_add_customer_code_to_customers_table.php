<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('customer_code')->unique()->nullable()->after('id');
            $table->index(['company_id', 'customer_code']);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['customer_code']);
            $table->dropIndex(['company_id', 'customer_code']);
            $table->dropColumn('customer_code');
        });
    }
};