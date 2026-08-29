<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FIX #2: Ledger Entries Orphaning
 * =================================
 *
 * PROBLEM:
 *   ledger_entries.transaction_id currently uses a nullable foreign key
 *   with nullOnDelete().
 *
 *   When a transaction is deleted, its ledger entries remain but their
 *   transaction_id becomes NULL. This breaks the accounting audit trail.
 *
 * SOLUTION:
 *   Change the foreign key's ON DELETE behavior to RESTRICT.
 *
 *   This prevents a transaction from being deleted while ledger entries
 *   reference it.
 *
 * SAFETY:
 *   1. Verify that no orphaned ledger entries already exist.
 *   2. Remove the existing transaction_id foreign key.
 *   3. Add a new foreign key with RESTRICT on delete.
 *
 * IMPORTANT:
 *   This migration intentionally does NOT recreate the transaction_id
 *   column. The existing column definition is preserved.
 *
 * WORKFLOW:
 *   - Posted transactions should be reversed, not deleted.
 *   - Transactions with ledger entries cannot be deleted.
 *   - Draft transactions without ledger entries can still be deleted.
 */
return new class extends Migration
{
    /**
     * Apply the migration.
     */
    public function up(): void
    {
        // ============================================================
        // Step 1: Safety Check — Verify no orphaned entries exist
        // ============================================================

        $orphanedCount = DB::table('ledger_entries')
            ->whereNull('transaction_id')
            ->count();

        if ($orphanedCount > 0) {
            throw new \RuntimeException(
                "Cannot apply migration: {$orphanedCount} orphaned ledger entries found "
                . "(transaction_id IS NULL). "
                . "These records must be reviewed and cleaned up manually before "
                . "the foreign key constraint can be changed."
            );
        }

        // ============================================================
        // Step 2: Drop the existing foreign key
        // ============================================================

        $this->dropCurrentForeignKey();

        // ============================================================
        // Step 3: Add the new RESTRICT foreign key
        // ============================================================
        //
        // IMPORTANT:
        // Do NOT use foreignId() here because transaction_id already
        // exists. foreignId() would attempt to define the column again.
        //
        // We only need to define the foreign key constraint.
        // ============================================================

        Schema::table('ledger_entries', function (Blueprint $table): void {
            $table->foreign('transaction_id')
                ->references('id')
                ->on('transactions')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        // ============================================================
        // Step 1: Drop the RESTRICT foreign key
        // ============================================================

        $this->dropCurrentForeignKey();

        // ============================================================
        // Step 2: Restore the original NULL ON DELETE behavior
        // ============================================================
        //
        // The existing transaction_id column is preserved.
        // We only recreate its foreign key constraint.
        // ============================================================

        Schema::table('ledger_entries', function (Blueprint $table): void {
            $table->foreign('transaction_id')
                ->references('id')
                ->on('transactions')
                ->nullOnDelete();
        });
    }

    /**
     * Drop the current foreign key on transaction_id.
     *
     * MySQL/MariaDB:
     *   Reads the actual constraint name from INFORMATION_SCHEMA.
     *
     * SQLite:
     *   Uses Laravel's schema builder.
     */
    private function dropCurrentForeignKey(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            // ========================================================
            // MySQL / MariaDB
            // ========================================================
            //
            // IMPORTANT:
            // COLUMN_NAME does NOT exist in
            // INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS.
            //
            // KEY_COLUMN_USAGE contains COLUMN_NAME and the referenced
            // table/column information.
            // ========================================================

            $constraint = DB::selectOne(
                "SELECT DISTINCT CONSTRAINT_NAME
                 FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'ledger_entries'
                   AND COLUMN_NAME = 'transaction_id'
                   AND REFERENCED_TABLE_NAME IS NOT NULL
                 LIMIT 1"
            );

            if ($constraint !== null) {
                $constraintName = (string) $constraint->CONSTRAINT_NAME;

                // Quote the constraint name safely.
                $quotedConstraintName = '`' .
                    str_replace('`', '``', $constraintName) .
                    '`';

                DB::statement(
                    "ALTER TABLE `ledger_entries`
                     DROP FOREIGN KEY {$quotedConstraintName}"
                );
            }

            return;
        }

        // ============================================================
        // SQLite / Other supported Laravel drivers
        // ============================================================

        Schema::table('ledger_entries', function (Blueprint $table): void {
            $table->dropForeign(['transaction_id']);
        });
    }
};
