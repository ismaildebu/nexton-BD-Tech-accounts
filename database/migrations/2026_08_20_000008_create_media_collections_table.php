<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `account_id` is the existing chart-of-accounts Account receiving the
     * money (Cash/Bank/Mobile Banking) — reuses Account, no new ledger model.
     *
     * `transaction_id` is nullable and unused in Phase 1. It mirrors the
     * same reserved-for-later-integration column already present on
     * purchase_bills, so a future LedgerPostingService hook can link a
     * Collection to its posted Transaction without a schema change.
     */
    public function up(): void
    {
        Schema::create('media_collections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('media_party_id');
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('transaction_id')->nullable();

            $table->decimal('amount', 15, 2);
            $table->string('payment_method')->default('Cash'); // Cash, Bank, Mobile Banking, Cheque, Other
            $table->date('collection_date');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('media_party_id')->references('id')->on('media_parties')->onDelete('restrict');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('restrict');
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['company_id', 'collection_date']);
            $table->index(['media_party_id', 'collection_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_collections');
    }
};