<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_orders', function (Blueprint $table) {
            $table->unsignedInteger('demand_quantity')
                ->default(0)
                ->after('print_date');

            $table->decimal('buffer_percentage', 5, 2)
                ->default(0)
                ->after('demand_quantity');

            $table->unsignedInteger('buffer_quantity')
                ->default(0)
                ->after('buffer_percentage');

            $table->unsignedInteger('final_quantity')
                ->default(0)
                ->after('buffer_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('print_orders', function (Blueprint $table) {
            $table->dropColumn([
                'demand_quantity',
                'buffer_percentage',
                'buffer_quantity',
                'final_quantity',
            ]);
        });
    }
};