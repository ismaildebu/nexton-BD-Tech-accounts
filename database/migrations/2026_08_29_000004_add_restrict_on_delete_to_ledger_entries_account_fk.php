<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop existing account_id foreign key
        $this->dropExistingAccountForeignKey();

        // Add new RESTRICT foreign key
        Schema::table('ledger_entries', function (Blueprint $table): void {
            $table->foreign('account_id')
                ->references('id')
                ->on('accounts')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        $this->dropExistingAccountForeignKey();

        Schema::table('ledger_entries', function (Blueprint $table): void {
            $table->foreign('account_id')
                ->references('id')
                ->on('accounts')
                ->cascadeOnDelete();
        });
    }

    private function dropExistingAccountForeignKey(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $constraint = DB::selectOne(
                "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'ledger_entries'
                   AND COLUMN_NAME = 'account_id'
                   AND REFERENCED_TABLE_NAME IS NOT NULL
                 LIMIT 1"
            );

            if ($constraint) {
                DB::statement(
                    "ALTER TABLE `ledger_entries` DROP FOREIGN KEY `{$constraint->CONSTRAINT_NAME}`"
                );
            }
        }
    }
};