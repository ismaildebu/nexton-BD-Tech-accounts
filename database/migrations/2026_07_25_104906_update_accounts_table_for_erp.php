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

            if (!Schema::hasColumn('accounts', 'nature')) {
                $table->string('nature')->default('General')->after('account_type');
            }

            if (!Schema::hasColumn('accounts', 'level')) {
                $table->unsignedTinyInteger('level')->default(1)->after('parent_id');
            }

            if (!Schema::hasColumn('accounts', 'color')) {
                $table->string('color', 20)->nullable()->after('level');
            }

            if (!Schema::hasColumn('accounts', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('color');
            }

            if (!Schema::hasColumn('accounts', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_system');
            }

            if (!Schema::hasColumn('accounts', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {

            if (Schema::hasColumn('accounts', 'nature')) {
                $table->dropColumn('nature');
            }

            if (Schema::hasColumn('accounts', 'level')) {
                $table->dropColumn('level');
            }

            if (Schema::hasColumn('accounts', 'color')) {
                $table->dropColumn('color');
            }

            if (Schema::hasColumn('accounts', 'is_system')) {
                $table->dropColumn('is_system');
            }

            if (Schema::hasColumn('accounts', 'is_active')) {
                $table->dropColumn('is_active');
            }

            if (Schema::hasColumn('accounts', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};