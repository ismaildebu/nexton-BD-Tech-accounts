<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $indexName): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return collect(
                DB::select("PRAGMA index_list('product_stocks')")
            )->contains(
                fn ($index) => ($index->name ?? null) === $indexName
            );
        }

        return collect(
            DB::select('SHOW INDEX FROM product_stocks')
        )->contains(
            fn ($index) => ($index->Key_name ?? null) === $indexName
        );
    }

    private function companyForeignKeyExists(): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $foreignKeys = DB::select(
                "PRAGMA foreign_key_list('product_stocks')"
            );

            return collect($foreignKeys)->contains(
                fn ($foreignKey) =>
                    ($foreignKey->from ?? null) === 'company_id'
                    && ($foreignKey->table ?? null) === 'companies'
                    && ($foreignKey->to ?? null) === 'id'
            );
        }

        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'product_stocks')
            ->where('COLUMN_NAME', 'company_id')
            ->where('REFERENCED_TABLE_NAME', 'companies')
            ->exists();
    }

    public function up(): void
    {
        /*
         * Add company_id only when the migration has not partially
         * executed yet.
         */
        if (! Schema::hasColumn('product_stocks', 'company_id')) {
            Schema::table('product_stocks', function (Blueprint $table) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('id');
            });

            DB::statement('
                UPDATE product_stocks
                SET company_id = (
                    SELECT products.company_id
                    FROM products
                    INNER JOIN warehouses
                        ON warehouses.id = product_stocks.warehouse_id
                    WHERE products.id = product_stocks.product_id
                      AND products.company_id = warehouses.company_id
                )
                WHERE EXISTS (
                    SELECT 1
                    FROM products
                    INNER JOIN warehouses
                        ON warehouses.id = product_stocks.warehouse_id
                    WHERE products.id = product_stocks.product_id
                      AND products.company_id = warehouses.company_id
                )
            ');

            $invalidRows = DB::table('product_stocks')
                ->whereNull('company_id')
                ->count();

            if ($invalidRows > 0) {
                throw new RuntimeException(
                    "Cannot backfill company_id for {$invalidRows} product_stocks row(s)."
                );
            }

            if (DB::getDriverName() === 'mysql') {
                DB::statement('
                    ALTER TABLE product_stocks
                    MODIFY company_id BIGINT UNSIGNED NOT NULL
                ');
            } else {
                Schema::table('product_stocks', function (Blueprint $table) {
                    $table->unsignedBigInteger('company_id')
                        ->nullable(false)
                        ->change();
                });
            }
        }

        /*
         * Add supporting indexes if they do not already exist.
         */
        if (! $this->indexExists('product_stocks_company_id_product_id_index')) {
            Schema::table('product_stocks', function (Blueprint $table) {
                $table->index(
                    ['company_id', 'product_id'],
                    'product_stocks_company_id_product_id_index'
                );
            });
        }

        if (! $this->indexExists('product_stocks_company_id_warehouse_id_index')) {
            Schema::table('product_stocks', function (Blueprint $table) {
                $table->index(
                    ['company_id', 'warehouse_id'],
                    'product_stocks_company_id_warehouse_id_index'
                );
            });
        }

        /*
         * The existing unique index also supports the product_id
         * foreign key. Create a dedicated product_id index first,
         * so the old unique index can safely be removed.
         */
        if (! $this->indexExists('product_stocks_product_id_index')) {
            Schema::table('product_stocks', function (Blueprint $table) {
                $table->index(
                    'product_id',
                    'product_stocks_product_id_index'
                );
            });
        }

        /*
         * Replace the old global uniqueness rule with a tenant-scoped
         * uniqueness rule.
         */
        if ($this->indexExists('product_stocks_product_id_warehouse_id_unique')) {
            Schema::table('product_stocks', function (Blueprint $table) {
                $table->dropUnique(
                    'product_stocks_product_id_warehouse_id_unique'
                );
            });
        }

        if (! $this->indexExists('product_stocks_company_product_warehouse_unique')) {
            Schema::table('product_stocks', function (Blueprint $table) {
                $table->unique(
                    ['company_id', 'product_id', 'warehouse_id'],
                    'product_stocks_company_product_warehouse_unique'
                );
            });
        }

        /*
         * Add the company foreign key only once.
         */
        if (! $this->companyForeignKeyExists()) {
            Schema::table('product_stocks', function (Blueprint $table) {
                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->dropUnique(
                'product_stocks_company_product_warehouse_unique'
            );

            $table->dropForeign(['company_id']);

            $table->dropIndex(
                'product_stocks_company_id_product_id_index'
            );

            $table->dropIndex(
                'product_stocks_company_id_warehouse_id_index'
            );

            $table->dropIndex(
                'product_stocks_product_id_index'
            );

            $table->dropColumn('company_id');
        });

        Schema::table('product_stocks', function (Blueprint $table) {
            $table->unique(
                ['product_id', 'warehouse_id'],
                'product_stocks_product_id_warehouse_id_unique'
            );
        });
    }
};
