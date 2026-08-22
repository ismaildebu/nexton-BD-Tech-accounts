<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Printing vendor" reuses the existing Vendor model/table —
     * no new vendor-type model is created for Media.
     */
    public function up(): void
    {
        Schema::create('print_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('publication_id');
            $table->unsignedBigInteger('print_plan_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable(); // printing press, reuses Vendor

            $table->string('order_number');
            $table->date('order_date');
            $table->date('print_date')->nullable();

            $table->unsignedInteger('ordered_quantity')->default(0);
            $table->unsignedInteger('printed_quantity')->default(0);
            $table->unsignedInteger('received_quantity')->default(0);

            $table->enum('status', ['Draft', 'Ordered', 'Printing', 'Printed', 'Received', 'Cancelled'])
                ->default('Draft');

            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('publication_id')->references('id')->on('publications')->onDelete('cascade');
            $table->foreign('print_plan_id')->references('id')->on('print_plans')->onDelete('set null');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->unique(['company_id', 'order_number']);
            $table->index(['company_id', 'order_date']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_orders');
    }
};