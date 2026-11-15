<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('purchase_bill_payment_items')) {
            Schema::create('purchase_bill_payment_items', function (Blueprint $table) {
                $table->id();

                $table->foreignId('purchase_bill_payment_id')
                    ->constrained('purchase_bill_payments')
                    ->cascadeOnDelete();

                $table->foreignId('account_id')
                    ->constrained('accounts')
                    ->restrictOnDelete();

                $table->decimal('amount', 15, 4);

                $table->timestamps();
            });
        }

        /*
         * The first migration attempt may have created the table
         * successfully before failing while creating the index.
         *
         * Therefore, create the named index only when it does not
         * already exist.
         *
         * Schema::getIndexes() is database-agnostic and works with
         * both MySQL and SQLite test environments.
         */
        $indexExists = collect(
            Schema::getIndexes('purchase_bill_payment_items')
        )->contains(
            fn (array $index): bool =>
                ($index['name'] ?? null) === 'pb_payment_account_idx'
        );

        if (! $indexExists) {
            Schema::table('purchase_bill_payment_items', function (Blueprint $table) {
                $table->index(
                    ['purchase_bill_payment_id', 'account_id'],
                    'pb_payment_account_idx'
                );
            });
        }

        /*
         * Preserve legacy payment-account data when the old
         * purchase_bill_payments.account_id column exists.
         *
         * Some installations/test schemas may already use the new
         * header structure and therefore may not have account_id.
         * In that case there is nothing to migrate.
         */
        if (Schema::hasColumn('purchase_bill_payments', 'account_id')) {
            DB::statement(
                <<<'SQL'
                INSERT INTO purchase_bill_payment_items
                    (
                        purchase_bill_payment_id,
                        account_id,
                        amount,
                        created_at,
                        updated_at
                    )
                SELECT
                    p.id,
                    p.account_id,
                    p.amount,
                    p.created_at,
                    p.updated_at
                FROM purchase_bill_payments AS p
                WHERE p.account_id IS NOT NULL
                  AND NOT EXISTS (
                      SELECT 1
                      FROM purchase_bill_payment_items AS i
                      WHERE i.purchase_bill_payment_id = p.id
                  )
                SQL
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_bill_payment_items');
    }
};