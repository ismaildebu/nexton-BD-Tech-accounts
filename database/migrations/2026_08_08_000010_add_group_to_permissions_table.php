<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a `group` column to the permissions table so the Permission UI
 * can cluster permissions by business module (accounting, sales,
 * inventory, hr, system).
 *
 * Run AFTER the spatie `create_permission_tables` migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('permissions', 'group')) {
                $table->string('group', 100)->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            if (Schema::hasColumn('permissions', 'group')) {
                $table->dropColumn('group');
            }
        });
    }
};
