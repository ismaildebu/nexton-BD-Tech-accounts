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
        Schema::create('companies', function (Blueprint $table) {

            $table->id();

            $table->string('company_name');
            $table->string('owner_name')->nullable();

            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();

            $table->string('logo')->nullable();

            $table->text('address')->nullable();

            $table->string('city')->nullable();
            $table->string('country')->default('Bangladesh');

            $table->string('currency')->default('BDT');
            $table->string('currency_symbol')->default('৳');

            $table->string('financial_year')->nullable();

            $table->boolean('status')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};