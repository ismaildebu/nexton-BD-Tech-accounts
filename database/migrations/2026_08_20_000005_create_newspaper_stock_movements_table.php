<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only movement log, mirroring the existing stock_movements
     * (inventory) table pattern: `type` + signed `quantity` + a generic
     * polymorphic-style reference (reference_type/reference_id) back to
     * whichever document caused the movement (PrintOrder, MediaDistribution,
     * MediaReturn, ...). Running balance is derived by summing quantity,
     * never stored directly, so it never drifts.
     *
     * Convention: quantity is signed.
     *   opening, printed, received, adjustment(+) -> positive
     *   distribution, damage, adjustment(-)        -> negative
     *   return                                      -> positive (paper coming back into stock)
     */
    public function up(): void
    {
        Schema::create('newspaper_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('publication_id');

            $table->date('movement_date');
            $table->enum('type', [
                'opening',
                'printed',
                'received',
                'distribution',
                'return',
                'damage',
                'adjustment',
            ]);
            $table->integer('quantity'); // signed

            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('publication_id')->references('id')->on('publications')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            
            $table->index(
                ['company_id', 'publication_id', 'movement_date'],
                'media_stock_company_pub_date_idx'
            );
            
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newspaper_stock_movements');
    }
};