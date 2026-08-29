<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('plan_id')
                ->constrained('plans')
                ->restrictOnDelete();

            // Explicit lifecycle status. Kept as a plain string (not a native DB enum)
            // to stay easily extensible without further migrations.
            $table->string('status')->default('active');

            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        /*
        |--------------------------------------------------------------------------
        | Enforce "one active subscription per user" at the database level
        |--------------------------------------------------------------------------
        |
        | MySQL does not support a native partial/conditional unique index.
        | We simulate one with a STORED generated column that resolves to the
        | user_id only when status = 'active', and NULL otherwise. A UNIQUE
        | index on that generated column then guarantees at most one 'active'
        | row per user, while allowing unlimited 'cancelled'/'expired' rows
        | (MySQL unique indexes treat multiple NULLs as distinct, not duplicate).
        |
        | This is a safety net beneath the application-level row locking in
        | SubscriptionService; it does not replace it. The generated-column
        | syntax below is MySQL-specific, so it only runs on that driver -
        | the test suite (sqlite, per phpunit.xml) relies on the application
        | -level locking alone, which is exercised directly by feature tests.
        */
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement(<<<'SQL'
                ALTER TABLE subscriptions
                ADD COLUMN active_user_id BIGINT UNSIGNED
                GENERATED ALWAYS AS (CASE WHEN status = 'active' THEN user_id ELSE NULL END) STORED
            SQL);

            DB::statement(<<<'SQL'
                ALTER TABLE subscriptions
                ADD UNIQUE INDEX uq_subscriptions_one_active_per_user (active_user_id)
            SQL);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};