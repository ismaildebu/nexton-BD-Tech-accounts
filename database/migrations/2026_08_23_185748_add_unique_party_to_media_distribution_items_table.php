<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_distribution_items', function (Blueprint $table) {
            $table->unique(
                ['media_distribution_id', 'media_party_id'],
                'media_distribution_party_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('media_distribution_items', function (Blueprint $table) {
            $table->dropUnique('media_distribution_party_unique');
        });
    }
};