<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Media distribution now allows multiple runs per publication per day
 * (one per shift/batch), instead of the original "one run per day"
 * rule. The old unique constraint on (company_id, publication_id,
 * distribution_date) is dropped; the plain index on
 * (company_id, distribution_date) from the create migration already
 * covers lookups by day, so it is left in place.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_distributions', function (Blueprint $table) {
            $table->dropUnique('media_dist_company_pub_date_unique');
        });
    }

    public function down(): void
    {
        Schema::table('media_distributions', function (Blueprint $table) {
            $table->unique(
                ['company_id', 'publication_id', 'distribution_date'],
                'media_dist_company_pub_date_unique'
            );
        });
    }
};