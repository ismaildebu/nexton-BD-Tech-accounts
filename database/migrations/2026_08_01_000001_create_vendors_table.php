<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('trade_license')->nullable();
            $table->string('tin')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->enum('balance_type', ['Payable', 'Advance'])->default('Payable');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')
                  ->references('id')
                  ->on('companies')
                  ->onDelete('cascade');

            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};