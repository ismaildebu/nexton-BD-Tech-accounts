<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
           
            $table->string('title');
            $table->enum('category', [
                'Trade License',
                'TIN',
                'VAT',
                'Agreement',
                'Tax',
                'Insurance',
                'Permit',
                'Certificate',
                'Other'
            ])->default('Other');

            $table->string('document_type')->nullable();
            $table->string('document_number');
            $table->string('reference_number')->nullable();

            $table->string('holder_name')->nullable();
            $table->string('issuing_authority')->nullable();
            $table->string('issue_place')->nullable();

            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('renewal_date')->nullable();

            $table->unsignedInteger('reminder_days')->default(30);

            $table->enum('status', [
                'active',
                'expired',
                'renewed',
                'cancelled',
                'archived',
                'pending'
            ])->default('active');

           
            $table->text('internal_notes')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type');
            $table->unsignedBigInteger('file_size');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
            $table->index('category');
            $table->index('document_number');
            $table->index('expiry_date');
            $table->index('is_active');
        });

        // Add foreign keys separately after all tables exist
        Schema::table('legal_documents', function (Blueprint $table) {
            $table->foreign('uploaded_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            $table->foreign('reviewed_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_documents');
    }
};