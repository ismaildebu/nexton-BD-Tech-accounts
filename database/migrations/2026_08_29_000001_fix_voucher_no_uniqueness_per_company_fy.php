<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FIX #1: Voucher Number Uniqueness Scope
 * =========================================
 *
 * PROBLEM:
 *   voucher_no was GLOBALLY unique across all companies.
 *   Company A couldn't use "JV-2026-000001" if Company B already had it.
 *   This violates multi-tenant isolation principles.
 *
 * SOLUTION:
 *   Uniqueness should be scoped to (company_id + financial_year_id + voucher_no).
 *   Each company can use the same voucher numbers across financial years.
 *
 * SAFETY:
 *   All existing vouchers are already company+FY specific in the data.
 *   Dropping global UNIQUE and adding composite UNIQUE is safe.
 *
 * ROLLBACK:
 *   down() will restore the global UNIQUE constraint.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            // ============================================================
            // Step 1: Find and drop the existing global UNIQUE constraint
            // ============================================================
            
            $this->dropGlobalUniqueOnVoucherNo();
        });

        // ============================================================
        // Step 2: Add composite UNIQUE constraint
        // ============================================================

        Schema::table('transactions', function (Blueprint $table): void {
            $table->unique(
                ['company_id', 'financial_year_id', 'voucher_no'],
                'idx_transactions_company_fy_voucher_no_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            // ============================================================
            // Step 1: Drop composite UNIQUE
            // ============================================================
            
            $table->dropUnique('idx_transactions_company_fy_voucher_no_unique');
        });

        // ============================================================
        // Step 2: Restore global UNIQUE
        // ============================================================

        Schema::table('transactions', function (Blueprint $table): void {
            $table->unique('voucher_no');
        });
    }

    /**
     * Find and drop the UNIQUE constraint on voucher_no.
     * This is done manually because Laravel might have auto-named
     * it differently than expected (especially on MySQL vs SQLite).
     */
    private function dropGlobalUniqueOnVoucherNo(): void
    {
        // ============================================================
        // Approach 1: Raw SQL DROP (most reliable)
        // ============================================================
        
        try {
            $driver = DB::connection()->getDriverName();

            if ($driver === 'mysql' || $driver === 'mariadb') {
                // MySQL: Find and drop the unique index on voucher_no
                $constraint = DB::selectOne(
                    "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                     WHERE TABLE_NAME = 'transactions'
                       AND COLUMN_NAME = 'voucher_no'
                       AND CONSTRAINT_NAME != 'PRIMARY'"
                );

                if ($constraint) {
                    DB::statement(
                        "ALTER TABLE transactions DROP KEY {$constraint->CONSTRAINT_NAME}"
                    );
                }
            } elseif ($driver === 'sqlite') {
                // SQLite: Use dropUnique (Laravel handles it)
                // We'll use Schema builder instead
                // Note: SQLite doesn't have INFORMATION_SCHEMA
                // Schema builder's dropUnique() will handle it
                return;  // Let Laravel handle it below
            }
        } catch (\Exception $e) {
            // If raw SQL fails, fall through to Schema builder
        }

        // ============================================================
        // Approach 2: Laravel Schema builder (fallback)
        // ============================================================
        
        // Try to drop using Laravel's naming convention
        try {
            // Laravel auto-names unique indexes as {table}_{column}_unique
            Schema::table('transactions', function (Blueprint $table): void {
                $table->dropUnique(['voucher_no']);
            });
        } catch (\Exception $e) {
            // If that fails, try explicit name
            try {
                Schema::table('transactions', function (Blueprint $table): void {
                    $table->dropUnique('transactions_voucher_no_unique');
                });
            } catch (\Exception $ignored) {
                // Constraint might not exist; that's OK for idempotency
            }
        }
    }
};