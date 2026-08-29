<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->boolean('is_accounted')
                  ->default(false)
                  ->after('status')
                  ->comment('Whether accounting entries (Dr A/R, Cr Sales) have been created');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn('is_accounted');
        });
    }
};