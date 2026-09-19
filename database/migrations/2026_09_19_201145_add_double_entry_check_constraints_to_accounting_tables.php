<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("
                CREATE TRIGGER chk_transaction_detail_amounts_insert
                BEFORE INSERT ON transaction_details
                FOR EACH ROW
                WHEN NOT (
                    (NEW.debit_amount > 0 AND NEW.credit_amount = 0)
                    OR
                    (NEW.debit_amount = 0 AND NEW.credit_amount > 0)
                )
                BEGIN
                    SELECT RAISE(ABORT, 'Invalid transaction detail amounts');
                END
            ");

            DB::statement("
                CREATE TRIGGER chk_transaction_detail_amounts_update
                BEFORE UPDATE OF debit_amount, credit_amount ON transaction_details
                FOR EACH ROW
                WHEN NOT (
                    (NEW.debit_amount > 0 AND NEW.credit_amount = 0)
                    OR
                    (NEW.debit_amount = 0 AND NEW.credit_amount > 0)
                )
                BEGIN
                    SELECT RAISE(ABORT, 'Invalid transaction detail amounts');
                END
            ");

            DB::statement("
                CREATE TRIGGER chk_transaction_totals_balanced_insert
                BEFORE INSERT ON transactions
                FOR EACH ROW
                WHEN NEW.total_debit != NEW.total_credit
                BEGIN
                    SELECT RAISE(ABORT, 'Transaction totals must be balanced');
                END
            ");

            DB::statement("
                CREATE TRIGGER chk_transaction_totals_balanced_update
                BEFORE UPDATE OF total_debit, total_credit ON transactions
                FOR EACH ROW
                WHEN NEW.total_debit != NEW.total_credit
                BEGIN
                    SELECT RAISE(ABORT, 'Transaction totals must be balanced');
                END
            ");

            return;
        }

        DB::statement("
            ALTER TABLE transaction_details
            ADD CONSTRAINT chk_transaction_detail_amounts
            CHECK (
                (debit_amount > 0 AND credit_amount = 0)
                OR
                (debit_amount = 0 AND credit_amount > 0)
            )
        ");

        DB::statement("
            ALTER TABLE transactions
            ADD CONSTRAINT chk_transaction_totals_balanced
            CHECK (
                total_debit = total_credit
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("
                DROP TRIGGER IF EXISTS chk_transaction_detail_amounts_insert
            ");

            DB::statement("
                DROP TRIGGER IF EXISTS chk_transaction_detail_amounts_update
            ");

            DB::statement("
                DROP TRIGGER IF EXISTS chk_transaction_totals_balanced_insert
            ");

            DB::statement("
                DROP TRIGGER IF EXISTS chk_transaction_totals_balanced_update
            ");

            return;
        }

        DB::statement("
            ALTER TABLE transaction_details
            DROP CHECK chk_transaction_detail_amounts
        ");

        DB::statement("
            ALTER TABLE transactions
            DROP CHECK chk_transaction_totals_balanced
        ");
    }
};