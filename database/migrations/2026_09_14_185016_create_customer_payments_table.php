<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            
            // Multi-tenant
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            
            // Customer who paid
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            
            // Invoice reference (invoice_number)
            $table->string('reference_id', 100); // Invoice number like "INV-001"
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            
            // Payment details
            $table->decimal('amount', 18, 4);
            $table->date('payment_date');
            $table->string('payment_method', 50); // 'sslcommerz', 'bkash', 'bank_transfer', 'cheque'
            
            // Payment gateway reference
            $table->string('transaction_reference', 255)->nullable()->unique(); // Gateway transaction ID
            
            // Voucher link (auto-created voucher)
            $table->foreignId('voucher_id')->nullable()->constrained('transactions')->nullOnDelete();
            
            // Status
            $table->enum('status', ['pending', 'received', 'verified', 'failed', 'cancelled'])->default('pending');
            
            // Admin verification
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            
            // Extra info
            $table->json('metadata')->nullable(); // Payment gateway response
            $table->text('notes')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'customer_id']);
            $table->index(['status']);
            $table->index(['reference_id']);
            $table->index(['payment_date']);
            $table->unique(['company_id', 'transaction_reference']); // Prevent duplicate gateway response
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_payments');
    }
};