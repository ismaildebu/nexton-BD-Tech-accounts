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
        Schema::table('print_orders', function (Blueprint $table) {
            $table->decimal('unit_printing_cost', 12, 4)
                ->default(0)
                ->after('received_quantity');

            $table->decimal('total_printing_cost', 14, 2)
                ->default(0)
                ->after('unit_printing_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('print_orders', function (Blueprint $table) {
            $table->dropColumn([
                'unit_printing_cost',
                'total_printing_cost',
            ]);
        });
    }
};