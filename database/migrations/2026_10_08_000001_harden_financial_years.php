<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Financial year integrity:
 *  - audit columns for closing / reopening a year
 *  - transactions.financial_year_id must never be silently set to NULL when a
 *    financial year is deleted (it was ON DELETE SET NULL, which orphaned
 *    posted vouchers). It becomes RESTRICT.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_years', function (Blueprint $table): void {
            $table->timestamp('closed_at')->nullable()->after('is_closed');

            $table->foreignId('closed_by')
                ->nullable()
                ->after('closed_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reopened_at')->nullable()->after('closed_by');

            $table->foreignId('reopened_by')
                ->nullable()
                ->after('reopened_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->text('reopen_reason')->nullable()->after('reopened_by');
        });

        if ($this->supportsForeignKeySwap()) {
            Schema::table('transactions', function (Blueprint $table): void {
                $table->dropForeign(['financial_year_id']);
            });

            Schema::table('transactions', function (Blueprint $table): void {
                $table->foreign('financial_year_id')
                    ->references('id')
                    ->on('financial_years')
                    ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        if ($this->supportsForeignKeySwap()) {
            Schema::table('transactions', function (Blueprint $table): void {
                $table->dropForeign(['financial_year_id']);
            });

            Schema::table('transactions', function (Blueprint $table): void {
                $table->foreign('financial_year_id')
                    ->references('id')
                    ->on('financial_years')
                    ->nullOnDelete();
            });
        }

        Schema::table('financial_years', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('closed_by');
            $table->dropConstrainedForeignId('reopened_by');
            $table->dropColumn(['closed_at', 'reopened_at', 'reopen_reason']);
        });
    }

    /**
     * The foreign key swap is only applied on MySQL/MariaDB (production).
     * The in-memory SQLite test database keeps the application-level guard.
     */
    private function supportsForeignKeySwap(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }
};
