<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_templates', function (Blueprint $table) {

            $table->boolean('is_system')
                ->default(true)
                ->after('business_type');

            $table->boolean('is_active')
                ->default(true)
                ->after('is_system');

        });
    }


    public function down(): void
    {
        Schema::table('account_templates', function (Blueprint $table) {

            $table->dropColumn([
                'is_system',
                'is_active'
            ]);

        });
    }
};