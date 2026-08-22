<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Paid Return and Free Return are tracked as two separate quantity
     * columns per line (not two separate tables), since they always
     * belong to the same party/return event and are reconciled together.
     */
    public function up(): void
    {
        Schema::create('media_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('publication_id');
            $table->unsignedBigInteger('media_distribution_id')->nullable();

            $table->date('return_date');
            $table->enum('status', ['Draft', 'Confirmed', 'Cancelled'])->default('Draft');

            $table->unsignedInteger('total_paid_return_quantity')->default(0);
            $table->unsignedInteger('total_free_return_quantity')->default(0);
            $table->unsignedInteger('total_return_quantity')->default(0);

            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('publication_id')->references('id')->on('publications')->onDelete('cascade');
            $table->foreign('media_distribution_id')->references('id')->on('media_distributions')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['company_id', 'return_date']);
        });

        Schema::create('media_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('media_return_id');
            $table->unsignedBigInteger('media_party_id');

            $table->unsignedInteger('paid_return_quantity')->default(0);
            $table->unsignedInteger('free_return_quantity')->default(0);
            $table->unsignedInteger('total_return_quantity')->default(0);

            $table->timestamps();

            $table->foreign('media_return_id')->references('id')->on('media_returns')->onDelete('cascade');
            $table->foreign('media_party_id')->references('id')->on('media_parties')->onDelete('restrict');

            $table->unique(['media_return_id', 'media_party_id'], 'media_return_items_return_party_unique');
            $table->index('media_party_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_return_items');
        Schema::dropIfExists('media_returns');
    }
};