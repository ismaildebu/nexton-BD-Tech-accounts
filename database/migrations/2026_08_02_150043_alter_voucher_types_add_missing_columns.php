<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voucher_types', function (Blueprint $table): void {

            if (! Schema::hasColumn('voucher_types', 'nature')) {
                $table->enum('nature', [
                    'journal',
                    'payment',
                    'receipt',
                    'contra',
                    'opening',
                ])->default('journal')->after('code');
            }

            if (! Schema::hasColumn('voucher_types', 'prefix')) {
                $table->string('prefix', 10)->nullable()->after('nature');
            }

            if (! Schema::hasColumn('voucher_types', 'last_number')) {
                $table->unsignedInteger('last_number')->default(0)->after('prefix');
            }

            if (! Schema::hasColumn('voucher_types', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('last_number');
            }

            if (! Schema::hasColumn('voucher_types', 'description')) {
                $table->text('description')->nullable()->after('is_active');
            }

            if (! Schema::hasColumn('voucher_types', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Existing data-তে nature ও prefix set করুন
        DB::statement("UPDATE voucher_types SET nature = 'journal' WHERE code = 'JV'");
        DB::statement("UPDATE voucher_types SET nature = 'payment' WHERE code = 'PV'");
        DB::statement("UPDATE voucher_types SET nature = 'receipt' WHERE code = 'RV'");
        DB::statement("UPDATE voucher_types SET nature = 'contra'  WHERE code = 'CV'");
        DB::statement("UPDATE voucher_types SET nature = 'opening' WHERE code = 'OV'");
        DB::statement("UPDATE voucher_types SET nature = 'journal' WHERE nature IS NULL OR nature = ''");
        DB::statement("UPDATE voucher_types SET prefix = code WHERE prefix IS NULL OR prefix = ''");
        DB::statement("UPDATE voucher_types SET is_active = 1 WHERE is_active IS NULL");
    }

    public function down(): void
    {
        Schema::table('voucher_types', function (Blueprint $table): void {
            $table->dropColumn([
                'nature',
                'prefix',
                'last_number',
                'is_active',
                'description',
                'deleted_at',
            ]);
        });
    }
};