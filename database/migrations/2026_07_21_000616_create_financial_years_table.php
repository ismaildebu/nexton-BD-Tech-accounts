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
        Schema::create('financial_years', function (Blueprint $table) {
    $table->id();

    $table->foreignId('company_id')
          ->constrained()
          ->cascadeOnDelete();

    $table->string('year_name');      // 2026-2027
    $table->date('start_date');
    $table->date('end_date');

    $table->boolean('is_active')->default(false);
    $table->boolean('is_closed')->default(false);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_years');
    }
};
