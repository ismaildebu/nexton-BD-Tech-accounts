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
        Schema::create('plan_features', function (Blueprint $table) {

            $table->id();

            $table->foreignId('plan_id')
                ->constrained('plans')
                ->cascadeOnDelete();

            // Machine-readable feature identifier, e.g. 'companies', 'invoices_monthly',
            // 'advanced_reports', 'pdf_export'. Matched against PlanLimitService lookups.
            $table->string('feature_key');

            // Numeric limit semantics (see PlanLimitService):
            //   NULL = feature has no numeric limit (not applicable / unbounded by design)
            //   -1   = unlimited
            //   N    = maximum allowed quantity
            $table->integer('limit_value')->nullable();

            // Boolean on/off toggle for non-numeric features (e.g. backup, media, pdf_export).
            $table->boolean('is_enabled')->default(false);

            $table->timestamps();

            $table->unique(['plan_id', 'feature_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_features');
    }
};