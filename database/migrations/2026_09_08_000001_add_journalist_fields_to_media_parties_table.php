<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add journalist-specific columns to media_parties.
     *
     * The `type` enum is also extended from ['agent','hawker']
     * to ['agent','hawker','journalist'].
     *
     * All new columns are nullable so existing Agent / Hawker rows
     * are never touched.
     */
    public function up(): void
    {
        // 1. Extend the `type` enum
        DB::statement(
            "ALTER TABLE media_parties MODIFY COLUMN type ENUM('agent','hawker','journalist') NOT NULL"
        );

        // 2. Add journalist-specific columns and audit field
        Schema::table('media_parties', function (Blueprint $table) {
            $table->string('beat')->nullable()->after('type');
            $table->string('email')->nullable()->after('phone');
            $table->string('district')->nullable()->after('area');
            $table->string('media_outlet')->nullable()->after('district');
            $table->decimal('commission_percent', 5, 2)->nullable()->after('free_percentage');

            $table->foreignId('created_by')
                ->nullable()
                ->after('company_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

   public function down(): void
{
    Schema::table('media_parties', function (Blueprint $table) {
        $table->dropColumn([
            'beat',
            'email',
            'district',
            'media_outlet',
            'commission_percent',
        ]);
    });

    DB::statement(
        "ALTER TABLE media_parties MODIFY COLUMN type ENUM('agent','hawker') NOT NULL"
    );
}
};