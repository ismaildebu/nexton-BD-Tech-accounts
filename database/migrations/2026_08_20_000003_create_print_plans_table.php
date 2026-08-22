<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('publication_id');

            $table->date('plan_date');

            // Reference figures used to derive the recommendation
            $table->unsignedInteger('previous_distribution_quantity')->nullable();
            $table->unsignedInteger('average_distribution_quantity')->nullable();

            // System-calculated expectation
            $table->unsignedInteger('expected_paid_quantity')->default(0);
            $table->unsignedInteger('expected_free_quantity')->default(0);
            $table->unsignedInteger('expected_total_quantity')->default(0);
            $table->unsignedInteger('buffer_quantity')->default(0);
            $table->unsignedInteger('recommended_quantity')->default(0);

            // Human override
            $table->unsignedInteger('adjusted_quantity')->nullable();
            $table->text('adjustment_reason')->nullable();

            $table->enum('status', ['Draft', 'Submitted', 'Approved', 'Rejected'])->default('Draft');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('publication_id')->references('id')->on('publications')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

            // One plan per publication per day
            $table->unique(['company_id', 'publication_id', 'plan_date']);
            $table->index(['company_id', 'plan_date']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_plans');
    }
};