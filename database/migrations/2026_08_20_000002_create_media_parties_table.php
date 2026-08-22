<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agent and Hawker share this one table, distinguished only by `type`.
     * There is deliberately NO agent_id / hawker_id linking column here —
     * per business rule, Agent and Hawker are completely independent.
     */
    public function up(): void
    {
        Schema::create('media_parties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');

            $table->string('name');
            $table->enum('type', ['agent', 'hawker']);
            $table->string('code');
            $table->string('phone')->nullable();
            $table->string('alternate_phone')->nullable();
            $table->text('address')->nullable();
            $table->string('area')->nullable();

            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->enum('balance_type', ['Receivable', 'Payable', 'Advance'])->default('Receivable');
            $table->decimal('free_percentage', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_parties');
    }
};