<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
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
            $table->string('document_number')->unique();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type'); // pdf, jpg, png, etc.
            $table->unsignedBigInteger('file_size'); // in bytes
            $table->text('description')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            
            $table->foreign('company_id')
                  ->references('id')
                  ->on('companies')
                  ->onDelete('cascade');
            
            $table->foreign('uploaded_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
            
            $table->foreign('reviewed_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for better query performance
            $table->index('company_id');
            $table->index('category');
            $table->index('document_number');
            $table->index('expiry_date');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_documents');
    }
};
