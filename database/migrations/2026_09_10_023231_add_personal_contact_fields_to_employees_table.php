<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('alternate_phone', 20)
                ->nullable()
                ->after('phone');

            $table->string('nid_number', 50)
                ->nullable()
                ->after('alternate_phone');

            $table->string('photo_path')
                ->nullable()
                ->after('nid_number');

            $table->index(['company_id', 'nid_number']);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['employees_company_id_nid_number_index']);
            $table->dropColumn([
                'alternate_phone',
                'nid_number',
                'photo_path',
            ]);
        });
    }
};