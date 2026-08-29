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
        Schema::create('plans', function (Blueprint $table) {

            $table->id();

            // Stable machine-readable identifier, e.g. 'free', 'plus'.
            // Used in code/config instead of relying on the primary key.
            $table->string('key')->unique();

            $table->string('name');
            $table->text('description')->nullable();

            $table->decimal('price', 10, 2)->default(0);
            $table->string('billing_cycle')->default('monthly');

            // Only one plan should be the automatic default (Free).
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};