<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Header/item split so a single day's distribution run can hold
     * 100+ party lines without bloating a flat table (same reasoning
     * as PurchaseBill/PurchaseBillItem and SalesOrder/SalesOrderItem).
     */
    public function up(): void
    {
        Schema::create('media_distributions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('publication_id');

            $table->date('distribution_date');
            $table->enum('status', ['Draft', 'Confirmed', 'Cancelled'])->default('Draft');

            $table->unsignedInteger('total_paid_quantity')->default(0);
            $table->unsignedInteger('total_free_quantity')->default(0);
            $table->unsignedInteger('total_quantity')->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);

            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('publication_id')->references('id')->on('publications')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            // One distribution run per publication per day
           
            $table->unique(
                    ['company_id', 'publication_id', 'distribution_date'],
                    'media_dist_company_pub_date_unique'
                );
            $table->index(['company_id', 'distribution_date']);
        });

        Schema::create('media_distribution_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('media_distribution_id');
            $table->unsignedBigInteger('media_party_id');

            $table->unsignedInteger('paid_quantity')->default(0);
            $table->decimal('free_percentage', 5, 2)->default(0);
            $table->unsignedInteger('free_quantity')->default(0);
            $table->unsignedInteger('total_quantity')->default(0); // paid + free

            $table->decimal('rate', 10, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0); // paid_quantity * rate

            $table->unsignedInteger('returned_quantity')->default(0);
            $table->unsignedInteger('net_quantity')->default(0); // total_quantity - returned_quantity

            $table->timestamps();

            $table->foreign('media_distribution_id')->references('id')->on('media_distributions')->onDelete('cascade');
            $table->foreign('media_party_id')->references('id')->on('media_parties')->onDelete('restrict');

            // One line per party per distribution run
            $table->unique(['media_distribution_id', 'media_party_id'], 'media_dist_items_dist_party_unique');
            $table->index('media_party_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_distribution_items');
        Schema::dropIfExists('media_distributions');
    }
};