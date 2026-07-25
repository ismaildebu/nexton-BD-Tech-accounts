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
        Schema::table('accounts', function (Blueprint $table) {

            $table->string('nature')->nullable()->after('account_type');

            $table->integer('level')->default(1)->after('nature');

            $table->string('color')->nullable()->after('level');

            $table->boolean('is_system')->default(false)->after('color');

            $table->boolean('is_active')->default(true)->after('is_system');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {

            $table->dropColumn([
                'nature',
                'level',
                'color',
                'is_system',
                'is_active',
            ]);

        });
    }
};