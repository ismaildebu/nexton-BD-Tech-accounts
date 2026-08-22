<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 1 defined free_percentage / default_free_percentage as
     * decimal NOT NULL DEFAULT 0. Phase 2's fallback chain
     * (Party -> Publication -> System Default) needs to tell "no
     * override was set" apart from "an override of exactly 0% was
     * set" — which a NOT NULL default can't express. Making both
     * columns nullable (default null) fixes that without touching
     * any other Phase 1 field.
     */
    public function up(): void
    {
        Schema::table('media_parties', function (Blueprint $table) {
            $table->decimal('free_percentage', 5, 2)->nullable()->default(null)->change();
        });

        Schema::table('publications', function (Blueprint $table) {
            $table->decimal('default_free_percentage', 5, 2)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('media_parties', function (Blueprint $table) {
            $table->decimal('free_percentage', 5, 2)->nullable(false)->default(0)->change();
        });

        Schema::table('publications', function (Blueprint $table) {
            $table->decimal('default_free_percentage', 5, 2)->nullable(false)->default(0)->change();
        });
    }
};
