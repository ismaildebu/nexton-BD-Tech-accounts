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
        Schema::table('account_templates', function (Blueprint $table) {

            $table->string('business_type')
                ->default('All')
                ->after('account_type');

        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_templates', function (Blueprint $table) {

            $table->dropColumn('business_type');

        });
    }
};